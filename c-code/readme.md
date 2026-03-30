gcc -o nexus_clear_groups nexus_clear_groups.c -lcurl -lcjson
```

---

**Flow breakdown:**
```
nexus_get_repositories()          →  all repos (any type)
  └─ filter type == "group"       →  NexusGroupRepository[]
       └─ nexus_get_group_detail() →  populate .members[] per group
            └─ nexus_clear_group_members() →  PUT with empty memberNames[]

gcc -o nexus_content_selectors nexus_content_selectors.c -lcurl -lcjson