# Partner-Managed NAANs — Design Notes (v2)

Working notes from the design conversation between Tac (Museado Foundation)
and John Kunze (CDL), May 2026. To be refined and turned into a formal
proposal.

## Important update from the docs

Before going further: the ARK Alliance documentation already describes a
**service-provider** model for exactly the pattern we've been calling
"Museado-as-manager." From arks.org/about/ark-naans-and-systems:

> You may also use that form to request NAANs on behalf of other
> organizations. Your organization might represent a group of other
> organizations, possibly providing them services such as ARK minting and
> database management. Examples include a non-profit aggregator or a
> for-profit archival system vendor. To proceed, you would fill out the form
> and list your own organization as a "service provider". Note that a NAAN
> requested in this way is meant for an organization that directly curates or
> creates content to which ARKs will be assigned. Each such organization that
> you serve should have its own NAAN. Moreover, if a service provider does
> resolution for a dozen organizations, it would not be surprising if its
> resolver URL were registered with a dozen different NAANs.

This changes the framing significantly. Pattern B (Museado-as-manager) is
not a new concept — it's the existing service-provider mechanism. What we're
actually asking John for is **speed**, not **a new policy class**.

## What's actually new in our ask

Stripped to essentials, three things:

1. **Programmatic submission**: skip the Google Form, post structured issues
   directly to the GitHub repo via the API. No code changes needed at CDL.
2. **Vetted-partner fast path**: when issues come from a recognized service
   provider, the curator team can process them on a faster SLA because the
   submitter has already been reviewed.
3. **Mint-before-review** (the more ambitious ask): a reservation endpoint
   that hands out the next NAAN immediately; the GitHub issue's role becomes
   "verify metadata is complete," not "decide whether to issue."

The first two are minor. The third is the real change. They're separable —
you could do (1) and (2) without (3) and still substantially help the
workflow.

## The two patterns (clarified)

### Pattern A — Museado mints under its own NAAN

Museado has its own NAAN(s). When ScanStation digitizes anything, the digital
surrogates get ARKs under Museado's NAAN. The source institution doesn't
have or need an ARK. The persistence commitment is Museado's.

This is the courthouse case. The Rappahannock Circuit Court is not a NAAN
holder; the marriage licenses are physical objects in their custody;
Museado's ARKs identify the **digital scans Museado made**, not the original
documents themselves. Museado promises to keep those scan-references stable.

### Pattern B — Museado as a service provider

Museado holds NAANs **on behalf of** partner institutions, in the way the
ARK Alliance docs already describe. Each partner has their own NAAN; Museado
operates the resolver and minting infrastructure. The institution can
migrate to self-managed or to a different service provider; the NAAN goes
with them.

This is the existing service-provider mechanism. The only thing we'd need
from CDL is faster turnaround when submitting on behalf of vetted partners,
since the manual review cadence doesn't fit operational digitization
workflows.

## The Library of Virginia question

Virginia circuit court records are managed by the **Library of Virginia
(LVA)**, not by the courts themselves. Before going further, need to know:

- Does LVA have a NAAN?
- If yes — under what scheme do they mint, and for what kinds of objects?
- If no — does LVA have any persistent-identifier strategy at all?

A quick search of the public NAAN registry didn't surface an LVA
registration, but absence in search isn't proof. Ways to find out:

1. Search the canonical NAAN registry directly:
   https://github.com/CDLUC3/naan_reg_priv/tree/main/naan_records
2. Check Virginia Memory (LVA's digital collections platform) for any visible
   identifier scheme on their object pages.
3. Email LVA's Records Management & Imaging Services and ask directly.

This matters because:

- **If LVA has a NAAN**: the courthouse scan is potentially in their
  authoritative jurisdiction. Museado's identifier should be clearly a
  surrogate (Museado's digital copy) that doesn't pretend to be LVA's
  authoritative reference. Two-layer model: LVA's authoritative ARK and
  Museado's surrogate ARK, both pointing at related-but-different things.
- **If LVA has no NAAN**: bigger opportunity. Museado could potentially
  approach LVA as a service provider for Virginia circuit court
  digitization at a state-wide level. This is a much larger and more
  interesting conversation than scanning courthouses one at a time, and one
  that LVA might actually find institutionally compelling — they're
  responsible for these records but probably don't have the digitization
  bandwidth to do all 95 counties themselves.

## What "vouching" means (when it's needed)

In Pattern A (Museado-as-digitizer), there's no vouching needed. Museado is
just minting under its own NAAN for surrogate identifiers Museado itself
produces. The institution doesn't get a NAAN; nothing on the institution's
side requires verification.

In Pattern B (service provider), the vouching is for the *partner
institution* being a real organization. The basis is:

- **On-site presence.** A Museado representative is physically there, with
  the materials, confirming the institution exists and holds what they claim.
  Same provenance judgment archivists exercise when accessioning.
- **Museado's institutional standing.** Museado Foundation is a Virginia
  501(c)(3) that donates money and time to small cultural heritage
  institutions. The vouch is backed by Museado's institutional commitment,
  not by paperwork between Museado and the partner.

Most small institutions can't or won't sign formal agreements. The vouch
shouldn't depend on them being able to.

## The technical proposal: reserve → use → review

Today: apply → wait → receive NAAN → use.
Proposed: **reserve → use → review.**

- CDL publishes the next available NAAN at a known endpoint (atomic counter).
- An authorized partner fetches it. The fetch *is* the reservation; it
  increments the counter atomically.
- The partner uses the NAAN immediately for minting and labeling.
- The partner submits the metadata as a GitHub issue (via API; no Google
  Form needed).
- The GitHub issue's purpose shifts from "should we issue a NAAN?" to "is
  this metadata complete?"
- Reserved NAANs whose metadata never arrives, or which fail review, get
  revoked and the slot freed.

ISBNs, DOIs, and most other persistent identifier systems work this way
already. Review is post-issuance, not gating issuance.

## The operational constraint that drives all this

When holding a real document — an 1850s marriage license, a fragile
photograph, a one-of-a-kind object — the workflow is touch-it-once. Scan,
label, return to the folder. Every additional handling is risk. The
proposal exists to fit that physical reality.

## What changes for CDL

- **Identifier format**: unchanged.
- **Registry storage**: unchanged.
- **Resolver rules (YAML)**: unchanged format.
- **Resolution behavior**: unchanged.
- **Persistence policy**: unchanged. Each NAAN has a named accountable party.
- **Service-provider concept**: unchanged. Already documented.
- **Assignment cadence**: faster, ideally programmatic for vetted providers.
- **Curator workflow**: review post-issuance instead of gating issuance.

## Vetting the providers

The fast-path mechanism only works if "authorized provider" means something.
Initial shape:

- A small number of vetted organizations (Museado would apply) get fast-path
  access.
- Vetting is a one-time review of the *organization*, not per-NAAN.
- Authorization can be revoked if a provider abuses the privilege or
  consistently submits bad metadata.
- All NAANs minted remain visible in the registry; nothing is hidden.

This separates the data-quality problem (per-submission) from the trust
problem (per-organization).

## Vocabulary

"Provisional" was wrong — implied a weaker class of identifier and a
transition event that doesn't need to exist. Better terminology:

- **"Service-provider NAAN"** — uses CDL's existing language, no invention
  needed.
- **"Provider-managed NAAN"** — variant of the same thing.
- **"Hosted NAAN"** — leans on the managed-DNS analogy.

The first option is probably right precisely because it's not new. Adopting
existing vocabulary signals that we're working within the system, not
proposing a parallel one.

## Open questions for the next session

- Where in CDLUC3/N2T should the reserve endpoint live (if pursued)?
- How is the "next available NAAN" published — JSON endpoint, file?
- Authentication: arks.org accounts, or separate API keys for providers?
- Expiration policy for reserved-but-unverified NAANs.
- What does revocation look like in practice — slot freed or permanently
  retired?
- Concurrency: handled by atomic counter, but worth confirming.
- LVA: does it have a NAAN? Does Museado want to be a service provider for
  Virginia state records, or just operate independently for non-LVA work?

## What Museado is offering

- Apply for service-provider status under whatever vetting CDL defines.
- Draft a PR against CDLUC3/N2T for the reserve endpoint, scoped to CDL's
  preferences.
- Operate as a real-world consumer of the endpoint, providing feedback.
- Document the provider-side protocol so other organizations can implement.
- Maintain Museado's NAANs and resolution indefinitely as part of the
  foundation's mission, regardless of how the upstream conversation goes.

## What Museado is asking for

A working session (about an hour) to walk through this, surface concerns,
and decide whether to pursue. If yes, Museado returns with a draft PR.

## What this is not

- Not a new identifier class.
- Not a way to bypass CDL's review of the registry.
- Not a federation of resolvers (earlier draft idea, abandoned).
- Not a sales tool or demo path for ScanStation customers.
- Not a critique of N2T, which works well for current users.

## To-do before the next session

- [ ] Check the NAAN registry for Library of Virginia.
- [ ] Check Virginia Memory for any visible identifier scheme.
- [ ] Decide whether LVA outreach is part of this proposal or separate.
- [ ] Decide whether to lead with (1)+(2) only, or include (3) as the bigger ask.
- [ ] Pressure-test whether Pattern B is needed day-one or can be deferred.
