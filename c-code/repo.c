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

/* ─── Structs ─────────────────────────────────────────────────────────────── */

#define MAX_REPOS       256
#define MAX_GROUP_MEMBERS 128
#define MAX_MEMBER_NAME  256

typedef struct {
    char name[256];
    char format[64];
    char type[64];
    char url[512];
} NexusRepository;

typedef struct {
    char   name[256];
    char   format[64];
    char   members[MAX_GROUP_MEMBERS][MAX_MEMBER_NAME];
    int    member_count;
} NexusGroupRepository;

/* ─── Helpers ─────────────────────────────────────────────────────────────── */

static CURL *make_curl(const char *url,
                       const char *auth,
                       ResponseBuffer *buf,
                       struct curl_slist *headers)
{
    CURL *curl = curl_easy_init();
    if (!curl) return NULL;
    curl_easy_setopt(curl, CURLOPT_URL,            url);
    curl_easy_setopt(curl, CURLOPT_HTTPHEADER,     headers);
    curl_easy_setopt(curl, CURLOPT_WRITEFUNCTION,  write_callback);
    curl_easy_setopt(curl, CURLOPT_WRITEDATA,      buf);
    curl_easy_setopt(curl, CURLOPT_TIMEOUT,        30L);
    curl_easy_setopt(curl, CURLOPT_FOLLOWLOCATION, 1L);
    if (auth) curl_easy_setopt(curl, CURLOPT_USERPWD, auth);
    return curl;
}

/* ─── 1. Get all repositories ─────────────────────────────────────────────── */

CURLcode nexus_get_repositories(const char      *base_url,
                                const char      *auth,
                                NexusRepository  repos[],
                                int              max_repos,
                                int             *count)
{
    *count = 0;
    char url[1024];
    snprintf(url, sizeof(url), "%s/service/rest/v1/repositories", base_url);

    ResponseBuffer buf = { malloc(1), 0 };
    buf.data[0] = '\0';

    struct curl_slist *headers = NULL;
    headers = curl_slist_append(headers, "Accept: application/json");

    CURL *curl = make_curl(url, auth, &buf, headers);
    if (!curl) { free(buf.data); curl_slist_free_all(headers); return CURLE_FAILED_INIT; }

    CURLcode res = curl_easy_perform(curl);
    if (res == CURLE_OK) {
        long http_code = 0;
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
        if (http_code == 200) {
            cJSON *json = cJSON_Parse(buf.data);
            if (json && cJSON_IsArray(json)) {
                int n = cJSON_GetArraySize(json);
                if (n > max_repos) n = max_repos;
                for (int i = 0; i < n; i++) {
                    cJSON *item   = cJSON_GetArrayItem(json, i);
                    cJSON *name   = cJSON_GetObjectItemCaseSensitive(item, "name");
                    cJSON *format = cJSON_GetObjectItemCaseSensitive(item, "format");
                    cJSON *type   = cJSON_GetObjectItemCaseSensitive(item, "type");
                    cJSON *rurl   = cJSON_GetObjectItemCaseSensitive(item, "url");
                    if (cJSON_IsString(name))   strncpy(repos[i].name,   name->valuestring,   sizeof(repos[i].name)   - 1);
                    if (cJSON_IsString(format)) strncpy(repos[i].format, format->valuestring, sizeof(repos[i].format) - 1);
                    if (cJSON_IsString(type))   strncpy(repos[i].type,   type->valuestring,   sizeof(repos[i].type)   - 1);
                    if (cJSON_IsString(rurl))   strncpy(repos[i].url,    rurl->valuestring,   sizeof(repos[i].url)    - 1);
                }
                *count = n;
                cJSON_Delete(json);
            }
        } else {
            fprintf(stderr, "[get_repos] HTTP %ld\n", http_code);
            res = CURLE_HTTP_RETURNED_ERROR;
        }
    }

    curl_easy_cleanup(curl);
    curl_slist_free_all(headers);
    free(buf.data);
    return res;
}

/* ─── 2. Fetch group detail (members list) for one repo ──────────────────── */

CURLcode nexus_get_group_detail(const char         *base_url,
                                const char         *auth,
                                const char         *format,
                                NexusGroupRepository *group)
{
    group->member_count = 0;

    /* GET /service/rest/v1/repositories/{format}/group/{name} */
    char url[1024];
    snprintf(url, sizeof(url),
             "%s/service/rest/v1/repositories/%s/group/%s",
             base_url, format, group->name);

    ResponseBuffer buf = { malloc(1), 0 };
    buf.data[0] = '\0';

    struct curl_slist *headers = NULL;
    headers = curl_slist_append(headers, "Accept: application/json");

    CURL *curl = make_curl(url, auth, &buf, headers);
    if (!curl) { free(buf.data); curl_slist_free_all(headers); return CURLE_FAILED_INIT; }

    CURLcode res = curl_easy_perform(curl);
    if (res == CURLE_OK) {
        long http_code = 0;
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
        if (http_code == 200) {
            cJSON *json = cJSON_Parse(buf.data);
            /* Response shape: { "group": { "memberNames": ["a","b",...] }, ... } */
            cJSON *group_obj = cJSON_GetObjectItemCaseSensitive(json, "group");
            cJSON *members   = group_obj
                               ? cJSON_GetObjectItemCaseSensitive(group_obj, "memberNames")
                               : NULL;
            if (members && cJSON_IsArray(members)) {
                int n = cJSON_GetArraySize(members);
                if (n > MAX_GROUP_MEMBERS) n = MAX_GROUP_MEMBERS;
                for (int i = 0; i < n; i++) {
                    cJSON *m = cJSON_GetArrayItem(members, i);
                    if (cJSON_IsString(m))
                        strncpy(group->members[i], m->valuestring, MAX_MEMBER_NAME - 1);
                }
                group->member_count = n;
            }
            cJSON_Delete(json);
        } else {
            fprintf(stderr, "[get_group_detail] %s HTTP %ld\n", group->name, http_code);
            res = CURLE_HTTP_RETURNED_ERROR;
        }
    }

    curl_easy_cleanup(curl);
    curl_slist_free_all(headers);
    free(buf.data);
    return res;
}

/* ─── 3. Remove all members from a single group (PUT with empty memberNames) */

CURLcode nexus_clear_group_members(const char         *base_url,
                                   const char         *auth,
                                   NexusGroupRepository *group)
{
    /* PUT /service/rest/v1/repositories/{format}/group/{name}
       Body: current config with "group.memberNames" set to []            */
    char url[1024];
    snprintf(url, sizeof(url),
             "%s/service/rest/v1/repositories/%s/group/%s",
             base_url, group->format, group->name);

    /* Minimal valid PUT body — memberNames is the only field we're clearing */
    cJSON *root       = cJSON_CreateObject();
    cJSON *group_obj  = cJSON_CreateObject();
    cJSON *empty_arr  = cJSON_CreateArray();
    cJSON_AddItemToObject(group_obj, "memberNames", empty_arr);
    cJSON_AddStringToObject(root, "name",   group->name);
    cJSON_AddStringToObject(root, "online", "true");
    cJSON_AddItemToObject(root, "group", group_obj);

    char *body = cJSON_PrintUnformatted(root);
    cJSON_Delete(root);

    ResponseBuffer buf = { malloc(1), 0 };
    buf.data[0] = '\0';

    struct curl_slist *headers = NULL;
    headers = curl_slist_append(headers, "Content-Type: application/json");
    headers = curl_slist_append(headers, "Accept: application/json");

    CURL *curl = make_curl(url, auth, &buf, headers);
    if (!curl) {
        free(body); free(buf.data); curl_slist_free_all(headers);
        return CURLE_FAILED_INIT;
    }

    curl_easy_setopt(curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_easy_setopt(curl, CURLOPT_POSTFIELDS,    body);

    CURLcode res = curl_easy_perform(curl);
    if (res == CURLE_OK) {
        long http_code = 0;
        curl_easy_getinfo(curl, CURLINFO_RESPONSE_CODE, &http_code);
        /* Nexus returns 204 No Content on success */
        if (http_code == 204) {
            printf("[clear_members] %-30s cleared (%d members removed)\n",
                   group->name, group->member_count);
            group->member_count = 0;
        } else {
            fprintf(stderr, "[clear_members] %s HTTP %ld — body: %s\n",
                    group->name, http_code, buf.data);
            res = CURLE_HTTP_RETURNED_ERROR;
        }
    }

    curl_easy_cleanup(curl);
    curl_slist_free_all(headers);
    free(buf.data);
    free(body);
    return res;
}

/* ─── 4. Orchestrator ─────────────────────────────────────────────────────── */

int main(void)
{
    curl_global_init(CURL_GLOBAL_DEFAULT);

    const char *base_url = "http://localhost:8081";
    const char *username = "admin";
    const char *password = "admin123";

    /* Build a single "user:pass" auth string for CURLOPT_USERPWD */
    char auth[512];
    snprintf(auth, sizeof(auth), "%s:%s", username, password);

    /* ── Step 1: fetch all repositories ─────────────────────────────────── */
    NexusRepository all_repos[MAX_REPOS];
    int repo_count = 0;

    printf("Fetching all repositories...\n");
    if (nexus_get_repositories(base_url, auth, all_repos, MAX_REPOS, &repo_count) != CURLE_OK) {
        fprintf(stderr, "Failed to fetch repositories\n");
        curl_global_cleanup();
        return 1;
    }
    printf("Total repositories found: %d\n\n", repo_count);

    /* ── Step 2: filter to group type ────────────────────────────────────── */
    NexusGroupRepository groups[MAX_REPOS];
    int group_count = 0;

    for (int i = 0; i < repo_count; i++) {
        if (strcmp(all_repos[i].type, "group") == 0) {
            strncpy(groups[group_count].name,   all_repos[i].name,   sizeof(groups[0].name)   - 1);
            strncpy(groups[group_count].format, all_repos[i].format, sizeof(groups[0].format) - 1);
            groups[group_count].member_count = 0;
            group_count++;
        }
    }
    printf("Group repositories found: %d\n\n", group_count);

    /* ── Step 3: enrich each group with its member list ──────────────────── */
    printf("Fetching group membership details...\n");
    for (int i = 0; i < group_count; i++) {
        CURLcode rc = nexus_get_group_detail(base_url, auth,
                                             groups[i].format, &groups[i]);
        if (rc != CURLE_OK) {
            fprintf(stderr, "  [!] Could not fetch detail for group '%s', skipping\n",
                    groups[i].name);
            continue;
        }
        printf("  %-30s [%s] — %d member(s)\n",
               groups[i].name, groups[i].format, groups[i].member_count);
        for (int m = 0; m < groups[i].member_count; m++)
            printf("      • %s\n", groups[i].members[m]);
    }
    printf("\n");

    /* ── Step 4: clear members from every group ──────────────────────────── */
    printf("Clearing all group members...\n");
    int success = 0, failed = 0;
    for (int i = 0; i < group_count; i++) {
        if (groups[i].member_count == 0) {
            printf("[clear_members] %-30s already empty, skipping\n", groups[i].name);
            continue;
        }
        CURLcode rc = nexus_clear_group_members(base_url, auth, &groups[i]);
        if (rc == CURLE_OK) success++; else failed++;
    }

    printf("\nDone. %d group(s) cleared, %d failed.\n", success, failed);

    curl_global_cleanup();
    return failed > 0 ? 1 : 0;
}