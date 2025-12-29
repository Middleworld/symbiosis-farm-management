# Plant Type JSON:API Reference

## Overview
The `plant_type` taxonomy now contains all plant variety records and is exposed via farmOS JSON:API. This document captures the key endpoints, authentication workflow, and query patterns needed to integrate the vocabulary into external systems.

## Authentication
Use the existing OAuth client credentials to obtain an access token.

```
curl -s -X POST \
  https://farmos.middleworldfarms.org/oauth/token \
  -d 'grant_type=client_credentials' \
  -d 'client_id=NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY' \
  -d 'client_secret=Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD' \
  -d 'scope=farm_manager'
```

The response returns an `access_token` string for use as a Bearer token.

## Base Endpoint
```
GET https://farmos.middleworldfarms.org/api/taxonomy_term/plant_type
```

### Field Selection
Restrict the response to commonly-used fields:

```
fields[taxonomy_term--plant_type]=name,description,parent,status,changed
```

Include additional fields (for example, `drupal_internal__tid`) as needed.

### Sample Request

```
curl --globoff -s \
  -H "Authorization: Bearer <ACCESS_TOKEN>" \
  -H "Accept: application/vnd.api+json" \
  'https://farmos.middleworldfarms.org/api/taxonomy_term/plant_type?fields%5Btaxonomy_term--plant_type%5D=name,description,parent,status,changed&page%5Blimit%5D=50'
```

### Response Highlights
- Each term is typed as `taxonomy_term--plant_type` and keyed by a UUID (`id`).
- `attributes.status` indicates whether the variety is published (boolean).
- `attributes.description` respects Drupal text format (plain or HTML).
- `relationships.parent` lists parent terms. Top-level crops use the special `virtual` identifier; nested varieties include the parent UUID and `meta.drupal_internal__target_id`.

Example excerpt:

```
{
  "type": "taxonomy_term--plant_type",
  "id": "b182a242-298a-470c-88e7-c463e1b444f5",
  "attributes": {
    "status": true,
    "name": "Abutilon Giant Flowering Mixed",
    "description": {
      "value": "Product Group: Flowers...",
      "format": "plain_text"
    },
    "changed": "2025-09-25T15:18:22+00:00"
  },
  "relationships": {
    "parent": {
      "data": [
        {
          "type": "taxonomy_term--plant_type",
          "id": "1ca7fcd3-7846-4503-a362-779cba5422a1",
          "meta": {
            "drupal_internal__target_id": 4692
          }
        }
      ]
    }
  }
}
```

## Pagination
- Default page size: 50 records.
- Increase page size: `page[limit]=100` (maximum allowed).
- Walk results: increment `page[offset]` or follow the `links.next` pointer returned in responses.

## Common Filters
- Published only: `filter[status][value]=1`
- Name search (case-insensitive contains):

```
filter[name][condition][path]=name&filter[name][condition][operator]=CONTAINS&filter[name][condition][value]=tomato
```

## Including Related Data
Fetch parent term details alongside child varieties:

```
include=parent
fields[taxonomy_term--plant_type]=name,parent,status
```

## Notes
- All historic `plant_variety` records now reside in `plant_type`.
- Cache headers require clients to honor `must-revalidate`; expect `ETag`/`Last-Modified` behavior managed by Drupal.
- Remember to refresh tokens periodically; client-credential tokens expire per OAuth configuration.
