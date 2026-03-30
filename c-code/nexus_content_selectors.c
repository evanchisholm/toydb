#include <stdio.h>
#include <stdlib.h>
#include <string.h>
#include <curl/curl.h>
#include <cjson/cJSON.h>

/* ─── Response buffer ─────────────────────────────────────────────────────── */

typedef struct {
    char  *data;
    size_t size;
} ResponseBuffer;

static size_t write_callback(void *contents, size_t size, size_t nmemb, void *userp)
{
    size_t real_size = size * nmemb;
    ResponseBuffer *buf = (ResponseBuffer *)userp;
    char *ptr = realloc(buf->data, buf->size + real_size + 1);
    if (!ptr) return 0;
    buf->data = ptr;
    memcpy(buf->data + buf->size, contents, real_size);
    buf->size += real_size;
    buf->data[buf->size] = '\0';
    return real_size;
}

/* ─── Struct ──────────────────────────────────────────────────────────────── */

#define MAX_CONTENT_SELECTORS 256
#define MAX_EXPRESSION_LEN    1024

typedef struct {
    char name[256];
    char type[64];        /* "csel" (CSEL) or "jexl" (legacy) */
    char description[512];
    char expression[MAX_EXPRESSION_LEN];
} NexusContentSelector;

/* ─── API call ────────────────────────────────────────────────────────────── */

/**
 * nexus_get_content_selectors
 *
 * Calls GET /service/rest/v1/security/content-selectors and populates
 * the caller-supplied `selectors` array.
 *
 * @param base_url   e.g. "http://localhost:8081"
 * @param auth       "username:password" for CURLOPT_USERPWD, or NULL for anon
 * @param selectors  Caller-allocated array to populate
 * @param max_items  Capacity of `selectors`
 * @param count      Set to number of selectors returned on success
 *
 * @return CURLE_OK on success, non-zero curl/HTTP error code on failure
 */
CURLcode nexus_get_content_selectors(const char           *base_url,
                                     const char           *auth,
                                     NexusContentSelector  selectors[],
                                     int                   max_items,
                                     int                  *count)
{
    if (!base_url || !selectors || !count) return CURLE_BAD_FUNCTION_ARGUMENT;
    *count = 0;

    /* ── Build URL ─────────────────────────────────────────────────────── */
    char url[1024];
    snprintf(url, sizeof(url),
             "%s/service/rest/v1/security/content-selectors", base_url);

    /* ── Init response buffer ──────────────────────────────────────────── */
    ResponseBuffer buf = { malloc(1), 0 };
    if (!buf.data) return CURLE_OUT_OF_MEMORY;
    buf.data[0] = '\0';

    /* ── Build headers ─────────────────────────────────────────────────── */
    struct curl_slist *headers = NULL;
    headers = curl_slist_append(headers, "Accept: application/json");

    /* ── Configure curl ────────────────────────────────────────────────── */
    CURL *curl = curl_easy_init();
    if (!curl) {
        free(buf.data);
        curl_slist_free_all(headers);
        return CURLE_FAILED_INIT;
    }

    curl_easy_setopt(curl, CURLOPT_URL,            url);
    curl_easy_setopt(curl, CURLOPT_HTTPHEADER,     headers);
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION,  write_callback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA,      &buf);
    curl_easy_setopt(curl, CURLOPT_TIMEOUT,        30L);
    curl_easy_setopt(curl, CURLOPT_FOLLOWLOCATION, 1L);
    if (auth) curl_easy_setopt(curl, CURLOPT_USERPWD, auth);

    /* Uncomment for self-signed TLS in dev environments:            */
    /* curl_easy_setopt(curl, CURLOPT_SSL_VERIFYPEER, 0L);           */

    /* ── Perform request ───────────────────────────────────────────────── */
    CURLcode res = curl_easy_perform(curl);

    if (res == CURLE_OK) {
        long http_code = 0;
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);

        switch (http_code) {
            case 200: {
                cJSON *json = cJSON_Parse(buf.data);
                if (!json || !cJSON_IsArray(json)) {
                    fprintf(stderr, "[content_selectors] Failed to parse JSON response\n");
                    res = CURLE_RECV_ERROR;
                    cJSON_Delete(json);
                    break;
                }

                int n = cJSON_GetArraySize(json);
                if (n > max_items) {
                    fprintf(stderr, "[content_selectors] Warning: %d selectors found, "
                                    "truncating to %d\n", n, max_items);
                    n = max_items;
                }

                for (int i = 0; i < n; i++) {
                    cJSON *item = cJSON_GetArrayItem(json, i);

                    cJSON *name  = cJSON_GetObjectItemCaseSensitive(item, "name");
                    cJSON *type  = cJSON_GetObjectItemCaseSensitive(item, "type");
                    cJSON *desc  = cJSON_GetObjectItemCaseSensitive(item, "description");
                    cJSON *expr  = cJSON_GetObjectItemCaseSensitive(item, "expression");

                    /* Zero-init so unused fields are always null-terminated */
                    memset(&selectors[i], 0, sizeof(NexusContentSelector));

                    if (cJSON_IsString(name))
                        strncpy(selectors[i].name,       name->valuestring,  sizeof(selectors[i].name)       - 1);
                    if (cJSON_IsString(type))
                        strncpy(selectors[i].type,       type->valuestring,  sizeof(selectors[i].type)       - 1);
                    if (cJSON_IsString(desc))
                        strncpy(selectors[i].description, desc->valuestring, sizeof(selectors[i].description) - 1);
                    if (cJSON_IsString(expr))
                        strncpy(selectors[i].expression, expr->valuestring,  sizeof(selectors[i].expression) - 1);
                }

                *count = n;
                cJSON_Delete(json);
                break;
            }
            case 401:
                fprintf(stderr, "[content_selectors] HTTP 401 Unauthorized — "
                                "check credentials\n");
                res = CURLE_HTTP_RETURNED_ERROR;
                break;
            case 403:
                fprintf(stderr, "[content_selectors] HTTP 403 Forbidden — "
                                "user lacks nx-security-all or equivalent privilege\n");
                res = CURLE_HTTP_RETURNED_ERROR;
                break;
            default:
                fprintf(stderr, "[content_selectors] Unexpected HTTP %ld\n", http_code);
                res = CURLE_HTTP_RETURNED_ERROR;
                break;
        }
    } else {
        fprintf(stderr, "[content_selectors] curl error: %s\n",
                curl_easy_strerror(res));
    }

    curl_easy_cleanup(curl);
    curl_slist_free_all(headers);
    free(buf.data);
    return res;
}

/* ─── Pretty printer ──────────────────────────────────────────────────────── */

void nexus_print_content_selectors(const NexusContentSelector selectors[],
                                   int count)
{
    printf("Content Selectors (%d)\n", count);
    printf("═══════════════════════════════════════════════════════════════\n");
    for (int i = 0; i < count; i++) {
        printf("  [%d] Name       : %s\n",   i + 1, selectors[i].name);
        printf("      Type       : %s\n",           selectors[i].type);
        if (selectors[i].description[0])
            printf("      Description: %s\n",       selectors[i].description);
        printf("      Expression : %s\n",           selectors[i].expression);
        printf("      ─────────────────────────────────────────────────────\n");
    }
}

/* ─── Example usage ───────────────────────────────────────────────────────── */

int main(void)
{
    curl_global_init(CURL_GLOBAL_DEFAULT);

    const char *base_url = "http://localhost:8081";

    char auth[512];
    snprintf(auth, sizeof(auth), "%s:%s", "admin", "admin123");

    NexusContentSelector selectors[MAX_CONTENT_SELECTORS];
    int count = 0;

    printf("Fetching content selectors from %s...\n\n", base_url);

    CURLcode rc = nexus_get_content_selectors(base_url, auth,
                                              selectors,
                                              MAX_CONTENT_SELECTORS,
                                              &count);
    if (rc != CURLE_OK) {
        fprintf(stderr, "nexus_get_content_selectors failed (rc=%d)\n", rc);
        curl_global_cleanup();
        return 1;
    }

    if (count == 0) {
        printf("No content selectors found.\n");
    } else {
        nexus_print_content_selectors(selectors, count);
    }

    curl_global_cleanup();
    return 0;
}