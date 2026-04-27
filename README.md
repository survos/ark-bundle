# SurvosArkBundle

Symfony bundle for ARK (Archival Resource Key) minting, binding, and resolution.

Requires PHP 8.4+ and Symfony 8.

## What it does

- Mints opaque ARK identifiers via the NOID algorithm (`daniel-km/noid`)
- Stores bindings in a Doctrine `ark_binding` pivot table (one SQL lookup to resolve any ARK)
- Auto-mints on `prePersist` for entities implementing `ArkableInterface`
- Redirects `GET /ark/{naan}/{name}` → entity's target URL (301)
- Serves ERC metadata via `?info` query parameter
- Exposes an admin browse entry via `#[EntityMeta]` (requires `survos/field-bundle`)
- Exports/imports bindings as JSONL for backups (requires `survos/jsonl-bundle`)

## Why ARK

- ARK Standard: https://arks.org
- ARK Spec (IETF draft): https://www.ietf.org/archive/id/draft-kunze-ark-34.html
- N2T Global Resolver: https://n2t.net

## Get a NAAN

Request your own NAAN before going to production:

- Application form: https://n2t.net/e/naan_request
- Registry: https://n2t.net/e/pub/naan_registry.txt

### Resolver rule for N2T registration

Use the template form so N2T constructs the correct path:

```
https://your-domain.org/ark/${content}
```

For ARK `ark:12345/x8rd9`, `${content}` = `12345/x8rd9`, producing
`https://your-domain.org/ark/12345/x8rd9` which matches the bundle's route exactly.

### Test ARK

Use `_probe` as the identifier string. The bundle serves `GET /ark/{naan}/_probe`
with HTTP 200 independently of any database state, so N2T's periodic checks
always pass:

```
ark:YOUR_NAAN/_probe
```

## Install

```bash
composer require survos/ark-bundle
```

Optional:

```bash
composer require survos/field-bundle   # admin navbar entry for ArkBinding
composer require survos/jsonl-bundle   # ark:export / ark:import commands
```

## Configuration

`config/packages/survos_ark.yaml`:

```yaml
survos_ark:
  naan: '12345'
  resolver_base_url: 'https://your-domain.org'
  shoulder: ''
  template: 'fk.reedeeedk'
  local_path: '/ark'
  db_type: 'sqlite'
  db_path: '%kernel.project_dir%/var/noid'
  auto_mint: true
  n2t_resolve: false
```

## Making an entity ARK-enabled

Implement `ArkableInterface` and use `ArkableTrait`:

```php
use Survos\ArkBundle\Contract\ArkableInterface;
use Survos\ArkBundle\Doctrine\ArkableTrait;

class Item implements ArkableInterface
{
    use ArkableTrait;

    public function getArkTarget(): string
    {
        return '/items/' . $this->id;   // relative URLs are prefixed with resolver_base_url
    }

    public function getArkObjectType(): string
    {
        return 'item';
    }
}
```

An ARK is minted automatically on first `persist()`. For entities with pre-generated
IDs (ULID/UUID), an `ArkBinding` row is also created in the same flush cycle.
Auto-increment entities are indexed lazily on first resolution or via `ark:reindex`.

## Resolution order

1. `ark_binding` table — single SQL query (primary path)
2. NOID database — in-process binary store (legacy/fallback)
3. Entity scan — iterates all `ArkableInterface` classes; lazily populates both stores on hit

## ArkBinding entity

`ark_binding` is a pivot table that links every minted ARK to its owning entity:

| Column | Description |
|---|---|
| `ark` | Full ARK string, e.g. `ark:/12345/ab12345` (unique) |
| `entityClass` | Short class name, e.g. `Item`, `Scan` |
| `entityId` | Entity identifier as string |
| `targetUrl` | Cached redirect URL |
| `label` | Optional human-readable label |

With `survos/field-bundle` installed, `ArkBinding` appears in the admin navbar
under the **ARK** group.

## Commands

| Command | Description |
|---|---|
| `ark:mint [count]` | Mint N ARKs |
| `ark:bind <name> <url>` | Manually bind a name to a URL |
| `ark:resolve <name>` | Look up a binding |
| `ark:validate <name>` | Check the check character |
| `ark:bulk-mint` | Mint ARKs for all unminted `ArkableInterface` entities |
| `ark:reindex` | Re-sync all NOID and `ArkBinding` entries from entity targets |
| `ark:report` | Report ARK coverage across entities |
| `ark:export` | Export `ArkBinding` rows as JSONL (stdout or `--output file.jsonl.gz`) |
| `ark:import` | Import `ArkBinding` rows from JSONL (stdin or `--input file.jsonl.gz`) |

## Routes

| Route | Description |
|---|---|
| `GET /ark/{naan}/{name}` | Resolve and redirect (301); append `?info` for ERC metadata |
| `GET /ark/{naan}/_probe` | Service health probe — always 200 |

## Persistence

| Layer | What it stores | Authoritative? |
|---|---|---|
| `ark_binding` Doctrine table | ARK → entity class, ID, target URL | **Yes — back this up** |
| NOID SQLite file (`var/noid/`) | Minting sequence + URL cache | No — rebuild with `ark:reindex` |

The NOID file is semi-ephemeral: if lost, run `ark:reindex` to repopulate it from
the `ark_binding` table. Available backends: `sqlite` (default), `mysql`, `pdo`,
`bdb` (BerkeleyDB), `lmdb`, `xml`.
