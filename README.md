# SurvosArkBundle

Symfony bundle for ARK (Archival Resource Key) minting, binding, and resolution.

This bundle lets any Doctrine entity become ARK-enabled by implementing a small interface, then handles:
- minting identifiers,
- storing bindings,
- resolving ARKs,
- and redirecting `/ark/{naan}/{name}` requests.

## Why ARK

- ARK Standard: https://arks.org
- ARK Spec (IETF draft): https://www.ietf.org/archive/id/draft-kunze-ark-34.html
- ARK Alliance: https://arks.org/about/
- N2T Global Resolver: https://n2t.net

## Apply For A NAAN

To run ARKs in production, request your own NAAN:

- NAAN application form: https://n2t.net/e/naan_request
- Registry listing: https://n2t.net/e/pub/naan_registry.txt

For memory organizations applying for a NAAN, include your collection and scan workflows in the application narrative. A practical policy is to ensure scans and related metadata carry your assigned number consistently in ARK URLs and institutional records.

## Install

```bash
composer require survos/ark-bundle
```

## Configuration

Create `config/packages/survos_ark.yaml`:

```yaml
survos_ark:
  naan: '12345'
  shoulder: 'fk'
  template: 'fk.reedeeedk'
  resolver_base_url: 'https://example.org'
  local_path: '/ark'
  db_type: 'lmdb'
  db_path: '%kernel.var_dir%/ark'
  auto_mint: true
  n2t_resolve: false
```

## Entity Contracts

- `Survos\ArkBundle\Contract\ArkableInterface`
- `Survos\ArkBundle\Contract\ArkQualifiableInterface`
- `Survos\ArkBundle\Contract\ErcMetadataInterface`

## Commands

- `ark:mint`
- `ark:bind`
- `ark:resolve`
- `ark:validate`
- `ark:bulk-mint`
- `ark:report`
- `ark:reindex`

## Route

The bundle exposes:

- `GET /ark/{naan}/{name}`

It also supports inflections like `?info` and policy lookup (`??`).
