# MuseadoArkBundle — Build Specification

## Overview

A Symfony bundle that provides ARK (Archival Resource Key) persistent identifier support for the Museado/Scanseum platform. Any entity in any bundle (ScanStation, Museado, ZM, MD, etc.) can become "ARK-able" by implementing a simple interface. The bundle owns minting, storage, resolution, and redirect routing.

**ARK Standard:** https://arks.org  
**ARK Spec (IETF draft):** https://www.ietf.org/archive/id/draft-kunze-ark-34.html  
**ARK Alliance:** https://arks.org/about/  
**NAAN Registry:** https://n2t.net/e/pub/naan_registry.txt  
**N2T Global Resolver:** https://n2t.net  

## Requirements

- PHP 8.4+
- Symfony 8.0+
- Doctrine ORM 3.x
- `daniel-km/noid` ^1.4 (Composer: `composer require daniel-km/noid`)
- `php-dba` extension (standard; verify LMDB handler available: `php -r "print_r(dba_handlers());"`)

---

## Package Identity

```
Package name:   museado/ark-bundle
Namespace:      Museado\ArkBundle
Bundle class:   Museado\ArkBundle\MuseadoArkBundle
```

---

## Configuration

### `config/packages/museado_ark.yaml`

```yaml
museado_ark:
  naan: '12345'                        # Your NAAN from California Digital Library (free registration)
  shoulder: 'fk'                       # Optional sub-namespace prefix (e.g. 'fk' for 'fake'/dev, 'p1' for production)
  template: 'fk.reedeeedk'            # Noid template string (see Noid4Php docs)
  resolver_base_url: 'https://museado.org'   # Canonical base URL for minted ARKs
  local_path: '/ark'                   # Route path prefix; produces /ark/{naan}/{name}
  db_type: 'lmdb'                      # lmdb (recommended), sqlite, xml, db4
  db_path: '%kernel.var_dir%/ark'     # Where the Noid minter database lives
  auto_mint: true                      # Auto-mint ARK on Doctrine prePersist if entity has none
  n2t_resolve: false                   # If true, expose ?info inflection metadata endpoint
```

### Bundle Extension

`Museado\ArkBundle\DependencyInjection\MuseadoArkExtension` loads the config tree and wires:
- `museado_ark.minter` service (NoidMinterService)
- `museado_ark.registry` service (ArkRegistry)
- `museado_ark.doctrine_listener` (ArkDoctrineListener, tagged as Doctrine event listener)

---

## Core Concepts

### ArkableInterface

Any entity that should receive an ARK implements this interface:

```php
namespace Museado\ArkBundle\Contract;

interface ArkableInterface
{
    public function getArk(): ?string;
    public function setArk(string $ark): static;

    /**
     * Returns the current canonical URL for this entity.
     * Used for ARK binding/rebinding on URL changes.
     */
    public function getArkTarget(): string;

    /**
     * Human-readable entity type label for ARK metadata (ERC who/what/when).
     * e.g. 'Scanned Document', 'Collection', 'Item'
     */
    public function getArkObjectType(): string;
}
```

### ArkableTrait

A convenience trait satisfying the storage half of `ArkableInterface`:

```php
namespace Museado\ArkBundle\Doctrine;

use Doctrine\ORM\Mapping as ORM;

trait ArkableTrait
{
    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $ark = null;

    public function getArk(): ?string
    {
        return $this->ark;
    }

    public function setArk(string $ark): static
    {
        $this->ark = $ark;
        return $this;
    }
}
```

---

## Services

### NoidMinterService

```
Museado\ArkBundle\Service\NoidMinterService
```

Wraps `daniel-km/noid`. Responsibilities:
- Open/create the Noid minter database on first use
- `mint(): string` — returns a new opaque identifier string (e.g. `fk8675309`)
- `bind(string $noid, string $url): void` — records the current target URL
- `rebind(string $noid, string $url): void` — updates binding (used when canonical URL changes)
- `resolve(string $noid): ?string` — looks up current URL for a noid
- `validate(string $noid): bool` — check digit validation

The full ARK is assembled as: `ark:/{naan}/{shoulder}{noid}`  
The actionable URL is: `{resolver_base_url}/ark/{naan}/{shoulder}{noid}`

### ArkRegistry

```
Museado\ArkBundle\Service\ArkRegistry
```

Central lookup service decoupled from Doctrine. Maps an ARK name component back to a live URL. Used by the redirect controller. Strategy:

1. Check Noid database binding (authoritative)
2. If unbound or stale, query Doctrine entity repository via tagged locator
3. Call `getArkTarget()` on found entity, rebind, return URL

This two-layer approach means ARKs survive even if the Noid DB is lost — they can be reconstructed from the entities.

### ArkDoctrineListener

```
Museado\ArkBundle\Doctrine\ArkDoctrineListener
```

Listens to:
- `prePersist` — if entity implements `ArkableInterface` and has no ARK, mint one and call `setArk()`
- `preUpdate` — if `getArkTarget()` has changed (detect via unit of work), rebind in Noid DB

Tagged as:
```php
#[AsDoctrineListener(event: Events::prePersist)]
#[AsDoctrineListener(event: Events::preUpdate)]
```

---

## Routing & Controller

### ArkRedirectController

```
Museado\ArkBundle\Controller\ArkRedirectController
```

Single action, registered via bundle's `routes.php`:

```php
// Route: GET /ark/{naan}/{name}
// Also handles: GET /ark/{naan}/{name}?info  (ERC metadata inflection)
// Also handles: GET /ark/{naan}/{name}??      (policy inflection)
```

Behavior:
- Validate naan matches configured NAAN (reject unknown NAANs with 404)
- Look up `{name}` via `ArkRegistry`
- If `?info` query param: return ERC metadata response (plain text, `erc:` format per spec)
- If `??` query param: return policy statement
- Otherwise: 301 redirect to `getArkTarget()` URL
- Unknown ARK: 404 with helpful message

### Route Registration

```php
// src/Resources/config/routes.php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return function (RoutingConfigurator $routes): void {
    $routes->add('museado_ark_resolve', '/ark/{naan}/{name}')
        ->controller('museado_ark.controller.redirect')
        ->methods(['GET']);
};
```

Auto-loaded via bundle's `getRoutes()` override or via `config/routes/museado_ark.yaml`.

---

## Console Commands

All commands use `#[AsCommand]` attribute. Namespace: `ark:`.

### `ark:mint`
```
Description: Mint one or more new ARK identifiers (without binding)
Arguments:   count (default: 1)
Options:     --output=json|text
```

### `ark:bind`
```
Description: Bind an existing ARK to a URL
Arguments:   ark, url
```

### `ark:resolve`
```
Description: Resolve an ARK to its current URL
Arguments:   ark
```

### `ark:validate`
```
Description: Validate an ARK check digit
Arguments:   ark
```

### `ark:bulk-mint`
```
Description: Scan all ArkableInterface entities missing ARKs and mint+bind them
Options:     --dry-run, --bundle=<name>, --entity=<FQCN>
```

### `ark:report`
```
Description: Report ARK status across all registered entities
Output:      Table: entity class | count | minted | unminted | last minted
```

### `ark:reindex`
```
Description: Rebuild Noid DB bindings from live entity getArkTarget() values
             (disaster recovery: reconstruct bindings after DB loss)
Options:     --dry-run
```

---

## ERC Metadata Inflection (`?info`)

When `?info` is appended to any ARK URL, return plain text in ERC (Electronic Resource Citation) format per the ARK spec:

```
erc:
who:   Rappahannock Historical Society
what:  Deed of Trust, John Smith to Mary Jones, 1887
when:  1887
where: https://scanseum.org/items/12345
```

The bundle provides a `ErcMetadataInterface` that entities can optionally implement for richer metadata. If not implemented, falls back to generic values from `getArkObjectType()`.

```php
namespace Museado\ArkBundle\Contract;

interface ErcMetadataInterface extends ArkableInterface
{
    public function getErcWho(): string;   // creator/contributor
    public function getErcWhat(): string;  // title/description
    public function getErcWhen(): string;  // date (free-form per spec)
}
```

---

## Integration Examples

### ScanStation Item entity

```php
use Museado\ArkBundle\Contract\ArkableInterface;
use Museado\ArkBundle\Contract\ErcMetadataInterface;
use Museado\ArkBundle\Doctrine\ArkableTrait;

class Item implements ArkableInterface, ErcMetadataInterface
{
    use ArkableTrait;

    public function getArkTarget(): string
    {
        return '/items/' . $this->getId(); // relative; bundle prepends resolver_base_url
    }

    public function getArkObjectType(): string
    {
        return 'Scanned Document';
    }

    public function getErcWho(): string { return $this->getInstitution()?->getName() ?? 'Unknown'; }
    public function getErcWhat(): string { return $this->getTitle(); }
    public function getErcWhen(): string { return $this->getDateCreated()?->format('Y') ?? 'unknown'; }
}
```

### Museado Collection entity

```php
class Collection implements ArkableInterface
{
    use ArkableTrait;

    public function getArkTarget(): string { return '/collections/' . $this->getSlug(); }
    public function getArkObjectType(): string { return 'Collection'; }
}
```

---

## Testing

- Unit tests for `NoidMinterService` (mint/bind/resolve/validate cycle)
- Unit tests for `ArkRegistry` (resolution strategies)
- Functional test for `ArkRedirectController` (redirect, ?info, ??, 404, wrong NAAN)
- Integration test for `ArkDoctrineListener` (auto-mint on persist, rebind on update)
- Console command tests for all `ark:*` commands
- PHPStan level 9, Psalm level 1

---

## File Structure

```
src/
  MuseadoArkBundle.php
  Contract/
    ArkableInterface.php
    ErcMetadataInterface.php
  Controller/
    ArkRedirectController.php
  DependencyInjection/
    MuseadoArkExtension.php
    Configuration.php
  Doctrine/
    ArkableTrait.php
    ArkDoctrineListener.php
  Service/
    NoidMinterService.php
    ArkRegistry.php
  Command/
    MintCommand.php
    BindCommand.php
    ResolveCommand.php
    ValidateCommand.php
    BulkMintCommand.php
    ReportCommand.php
    ReindexCommand.php
  Resources/
    config/
      services.php
      routes.php
tests/
  Unit/
    Service/
      NoidMinterServiceTest.php
      ArkRegistryTest.php
    Doctrine/
      ArkDoctrineListenerTest.php
  Functional/
    Controller/
      ArkRedirectControllerTest.php
  Command/
    BulkMintCommandTest.php
    ReportCommandTest.php
```

---

## References

- ARK Alliance: https://arks.org
- ARK Identifiers FAQ (LYRASIS): https://wiki.lyrasis.org/display/ARKs/ARK+Identifiers+FAQ
- ARK Spec IETF draft: https://www.ietf.org/archive/id/draft-kunze-ark-34.html
- Noid4Php (Daniel Berthereau): https://gitlab.com/Daniel-KM/Noid4Php
- Noid4Php on Packagist: https://packagist.org/packages/daniel-km/noid
- Omeka-S ARK module (reference implementation): https://github.com/Daniel-KM/Omeka-S-module-Ark
- ARKs-Service (U of Toronto, standalone PHP app): https://github.com/digitalutsc/arks-service
- Code4Lib article on ARKs-Service: https://journal.code4lib.org/articles/16774
- NAAN registration form: https://n2t.net/e/naan_request
- N2T global resolver: https://n2t.net
- ERC metadata format: https://www.dublincore.org/groups/kernel/spec/
