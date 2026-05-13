# Scope: AI-Native Book Closing Platform

## Product Intent

Build a production-quality accounting platform that acts as the source of truth for bookkeeping, receipt collection, transaction categorization, vendor normalization, reconciliation, and book closing.

The product is conceptually a focused custom version of Xero, but only for the workflows required to close books accurately and consistently. It should not attempt to become a full general-ledger, payroll, tax filing, inventory, or enterprise ERP system unless those capabilities are required for book closing.

The primary problem this product solves is fragmented accounting data across multiple financial systems. Mercury, Ramp, Brex, Stripe, and other systems each have their own transaction views, vendor naming, AI categorization, receipt handling, and accounting metadata. Those systems do not coordinate with each other, causing inconsistent books, duplicate vendor concepts, missing receipts, unclear categorization rules, and difficult month-end close.

This application must centralize those inputs, normalize them into first-party entities, apply consistent AI-assisted accounting decisions, preserve human overrides, and provide a workflow for reconciling transactions and closing books.

## Core Principles

- The application is the source of truth for accounting categorization, vendor identity, contacts, bank feed records, receipt records, invoice records, reconciliation state, and close state.
- External systems are data sources and optional downstream sync targets, not the canonical accounting model.
- All tenant-scoped data must belong to a team. Do not create global bank feeds, vendors, contacts, transactions, chart of accounts, invoices, receipts, categorization rules, reconciliation state, or close state.
- AI must assist with extraction, matching, categorization, and anomaly detection, but uncertain or conflicting results must enter a manual review workflow.
- AI categorization must optimize for consistency over novelty. Similar vendors and similar transactions should be categorized the same way unless a human explicitly overrides the decision.
- Human overrides must be durable and become future categorization signals.
- Every automated accounting decision must be explainable, auditable, reversible, and backed by immutable history.
- Auditability, chain of custody, record retention, and attribution are the highest-priority requirements in the system. This is a financial application and must be designed for IRS-grade record keeping from the beginning.
- No accounting-relevant record, decision, source document, AI run, integration event, user action, or state transition may be hard-deleted. Records may be voided, archived, superseded, or marked deleted, but the historical database record must remain.
- The system must be designed like a production SaaS application even if the first user is internal only.

## Compliance, Chain of Custody, and Immutable History

The platform must treat auditability as the primary product requirement. Every meaningful event must be recorded in the database with enough detail to reconstruct what happened, when it happened, who or what caused it, what data was used, and what changed as a result.

The system must maintain chain of custody for:

- Source emails.
- Source attachments.
- Uploaded documents.
- Provider-imported documents.
- Provider-imported transactions.
- Provider raw payloads.
- AI inputs.
- AI outputs.
- AI consensus attempts.
- Human review actions.
- Categorization changes.
- Vendor mapping changes.
- Receipt and invoice matching changes.
- Transfer pairing changes.
- Close period changes.
- External sync attempts and downstream provider updates.

The system must use append-only event or history records for accounting decisions. Mutable summary columns may exist for query performance and UI convenience, but they must never be the only source of truth for a decision.

For example, a bank transaction must not only have a single mutable `chart_account_id` field that gets overwritten. Categorization must be represented by a history of assignments, suggestions, approvals, overrides, voids, and effective-current state. The UI must be able to show that a transaction was originally categorized as subscriptions, later changed to travel, who changed it, when they changed it, and why.

Historical records must support:

- Team ownership.
- Entity type and entity ID.
- Event type.
- Event sequence or version.
- Previous value.
- New value.
- Effective current flag if applicable.
- Reason or explanation.
- Source, such as human, AI, integration, system rule, import, or backfill.
- Actor attribution.
- Related AI run if applicable.
- Related integration sync run if applicable.
- Related source document if applicable.
- Created timestamp.
- Superseded, voided, or reversed timestamp if applicable.

Actor attribution must be durable even if a user later changes or deletes their account.

Human-attributed records must store:

- Nullable `user_id`.
- Actor display name snapshot.
- Actor email snapshot.
- Actor profile photo URL or storage reference snapshot if available.
- Actor role or membership context if relevant.
- Actor team membership ID if available.

If a user is deleted, historical records must not cascade delete. The `user_id` may become null, but the denormalized actor attribution must remain so the UI can still show who performed the action.

If a user updates their name, email, or profile photo, the system needs a deliberate mechanism to update denormalized current-display attribution fields on historical records that still reference that user. This must be done without deleting or obscuring the original audit event payload. If the product needs both "who the user is now" and "what their profile looked like at the time of action", store both current-display attribution and action-time attribution.

Hard deletes are not allowed for financial records. For user-requested deletion, account records may be deactivated, anonymized where legally appropriate, or detached from live authentication, but accounting audit records must remain intact.

Every frontend screen that shows an accounting decision should be able to surface the relevant history, including prior values, current value, actor attribution, AI attribution, integration attribution, timestamps, and source evidence.

## Mandatory AI Implementation Requirement

All AI interactions must use the Laravel AI SDK. This is mandatory and non-negotiable.

Before implementing any AI feature, the agent or engineer must use Laravel Boost MCP documentation search to read the current Laravel AI SDK documentation. Do not rely on model memory for SDK syntax, capabilities, provider configuration, file input handling, PDF handling, structured output, streaming, tool calling, or multi-provider behavior.

Required AI usage areas include:

- Receipt and invoice document extraction.
- Email attachment and email body analysis.
- Transaction categorization.
- Vendor normalization and duplicate vendor detection.
- Matching receipts or invoices to bank feed transactions.
- Detecting missing receipts.
- Identifying likely transfers between accounts.
- Multi-model agreement checks.
- Manual review queue triage explanations.
- Suggested vendor integration opportunities.

The application must be designed so AI providers can be switched through the Laravel AI SDK without rewriting business logic. Provider-specific code must not leak into accounting domain services.

## Multi-Model AI Verification

For high-impact AI workflows, especially receipt extraction and transaction categorization, the platform must support running more than one AI model against the same input.

The system must compare model outputs and produce an agreement result.

At minimum, comparison must include:

- Vendor identity.
- Receipt or invoice number.
- Transaction date.
- Currency.
- Total amount.
- Tax amount if present.
- Line item descriptions.
- Line item quantities.
- Line item unit prices.
- Line item totals.
- Suggested chart of accounts entry.
- Suggested first-party vendor.
- Suggested bank transaction match.
- Confidence score.

If models materially disagree on required fields, the record must enter manual review. The application must store each model's raw structured output, confidence, provider, model name, prompt or prompt version, input file references, and final selected result.

Every AI call must be persisted as an auditable run. The system must store the exact AI input payload sent through the Laravel AI SDK, including prompts, messages, structured instructions, tool configuration, file references, document hashes, extracted text included in the request, provider, model, model parameters, and application prompt version. For large files, the database may store immutable storage references and content hashes rather than duplicating binary bytes, but the system must be able to reconstruct what the AI provider received.

Every AI response must be persisted, including raw provider response, parsed structured output, validation errors, confidence values, token usage if available, finish reason, latency, provider request IDs if available, and any safety or refusal metadata returned by the provider.

Consensus workflows must preserve every pass. If the first pass fails to reach agreement and the system performs a second pass, tie-breaker pass, adjudication pass, or human-assisted pass, that later run must be stored as a new historical AI run linked to the earlier runs. Never replace the first-pass input or output with the second-pass result.

The frontend must be able to surface AI provenance for any AI-assisted field, including which provider and model produced the suggestion, what inputs were used, what other models said, whether consensus was reached, and whether a human accepted or overrode the result.

The system must distinguish between:

- Model agreement with high confidence.
- Model agreement with low confidence.
- Model disagreement.
- Missing required data.
- Ambiguous accounting treatment.
- Possible duplicate receipt or invoice.
- Possible transfer rather than income or expense.

## Tenancy, Accounts, and Teams

The platform must support user accounts and teams.

Each authenticated user may belong to one or more teams. A user must be able to switch between teams. All accounting data shown in the UI must be scoped to the active team.

Team-scoped entities must include at minimum:

- Bank connections.
- Bank accounts.
- Bank feed transactions.
- First-party vendors.
- External vendor mappings.
- Contacts.
- Stripe customers.
- Invoices.
- Receipts.
- Documents and attachments.
- Chart of accounts.
- Categorization rules.
- AI extraction runs.
- AI categorization runs.
- Manual review items.
- Reconciliation sessions.
- Close periods.
- Audit log entries.
- Integration credentials and connection metadata.

No team should be able to access or infer another team's accounting data. Authorization checks must enforce team ownership for every read, write, sync, export, and AI operation.

## Chart of Accounts

Each team must have its own chart of accounts.

When a team is created, it should receive a starter chart of accounts suitable for a small software or services business. The starter chart must be copied into team-owned records, not shared globally in a way that would make edits affect other teams.

Users must be able to:

- Add chart of accounts entries.
- Edit chart of accounts entries.
- Disable or archive chart of accounts entries.
- Delete entries only when safe, such as when unused or after reassigning dependent records.
- Map imported transactions and line items to chart of accounts entries.
- Review AI-suggested account mappings before acceptance when confidence is low.

Chart of accounts entries should support at minimum:

- Team ownership.
- Account code.
- Account name.
- Account type.
- Optional subtype.
- Description.
- Active or archived state.
- System starter flag.
- Parent account if hierarchical accounts are supported.

The system must preserve historical categorization even if an account is later renamed or archived.

## First-Party Vendor Model

The application must define its own first-party vendor entity. This entity is the canonical vendor identity used for reporting, categorization, receipt matching, and spend aggregation.

External vendor names from direct bank APIs, Stripe Financial Connections, Mercury, Ramp, Brex, Stripe, email receipts, OCR, invoice PDFs, and other integrations must map into first-party vendors.

Example:

- Ramp may call the vendor `Delta`.
- Brex may call the vendor `Delta Airlines`.
- Mercury may use a merchant descriptor such as `DELTA AIR LINES ATLANTA GA`.
- The application should normalize these to a single first-party vendor, such as `Delta Air Lines`.

The first-party vendor model must support:

- Team ownership.
- Canonical display name.
- Legal name if known.
- Website.
- Tax ID if known.
- Default chart of accounts entry.
- Default expense category.
- Default receipt requirement policy.
- Default integration driver if one exists.
- AI-generated aliases.
- User-defined aliases.
- External vendor mappings.
- Historical categorization examples.
- Notes.
- Active or archived state.

Vendor normalization must use:

- Exact external vendor mappings.
- User-defined aliases.
- AI-generated similarity matching.
- Historical transaction patterns.
- Email sender domains.
- Receipt issuer names.
- Invoice metadata.
- Payment descriptors.
- Manual merge and split decisions.

The platform must support manually merging duplicate first-party vendors and manually splitting incorrectly merged vendors. Those actions must update future matching behavior and preserve an audit trail.

## External Vendor Mappings

Each external system may expose its own vendor, merchant, customer, counterparty, or contact concept. These must be stored as external vendor mappings linked to the team's first-party vendor.

External vendor mappings should support:

- Team ownership.
- Integration source, such as direct bank API, Stripe Financial Connections, Ramp, Brex, Mercury, Stripe, Outlook, Google, another email provider, or manual import.
- External vendor ID if available.
- External display name.
- External normalized name if available.
- External metadata payload.
- Linked first-party vendor.
- Confidence and matching method.
- Last seen timestamp.
- Sync status.

When a first-party vendor is changed in this application and the external provider supports updating its vendor concept, the application should push the change back to that provider. Ramp is known to support vendor updates and should be treated as a target for downstream sync.

If an external provider does not support vendor updates, the application should keep the local mapping as the source of truth and mark the external system as read-only for vendor identity.

## Bank Feeds and Financial Data Sources

The platform must ingest financial activity from multiple sources and normalize it into team-owned bank feed transactions.

Banking integrations should support two strategies:

- Direct provider APIs when a bank, card platform, or financial provider exposes a useful API.
- Stripe Financial Connections for broader bank feed coverage when a direct integration is not available or not worth building.

Initial direct API targets are based on current usage, but the architecture must not be locked to those providers:

- Mercury.
- Ramp.
- Brex.
- Stripe.

Stripe Financial Connections replaces Plaid as the preferred aggregator-style bank connection strategy. Do not plan or implement Plaid unless it is explicitly reintroduced later.

The system should investigate direct APIs for specific providers before defaulting to Stripe Financial Connections. If a direct API provides richer metadata, better receipt support, cardholder data, vendor records, writable vendor records, or more reliable transaction IDs, direct integration should be preferred. If a provider does not have a worthwhile direct API, Stripe Financial Connections should be used for account and transaction feeds.

Bank connections must support:

- Team ownership.
- Provider name.
- Connection status.
- Credential storage reference.
- Sync cursor or pagination state.
- Last successful sync timestamp.
- Last failed sync timestamp.
- Failure reason.
- Supported capabilities.
- Read-only versus read-write capability flags.

Bank accounts must support:

- Team ownership.
- Provider.
- External account ID.
- Display name.
- Institution name.
- Account type.
- Subtype.
- Currency.
- Last four digits if available.
- Current balance if available.
- Available balance if available.
- Active or archived state.

Bank feed transactions must support:

- Team ownership.
- Source provider.
- External transaction ID.
- Bank account.
- Posted date.
- Authorized date if available.
- Description.
- Merchant descriptor.
- Amount.
- Currency.
- Direction, such as debit, credit, or transfer.
- Running balance if available.
- Raw provider category.
- Raw provider vendor or merchant.
- Linked first-party vendor.
- Linked contact if applicable.
- Linked receipt.
- Linked invoice.
- Linked transfer counterpart transaction.
- Current categorization summary derived from immutable categorization history.
- Current suggested chart of accounts summary derived from AI and rule suggestion history.
- AI confidence.
- Review status.
- Reconciliation status.
- Close period.
- Raw provider payload.

Bank transactions must not rely on mutable chart-of-account columns as the sole record of categorization. The authoritative categorization record must be an append-only assignment and suggestion history that can show every prior category, every proposed category, every accepted category, every override, every actor, every AI run, and every timestamp.

The same real-world transaction may arrive through multiple sources, such as Stripe payout data and bank deposit data. The system must be able to link related records without duplicating income or fees.

## Email Receipt and Invoice Ingestion

The application must connect to email providers and scan for receipts, invoices, payment confirmations, and other accounting-relevant documents. Outlook should be the first email provider implemented, but the domain model and ingestion pipeline must also support Google/Gmail and future providers.

Email ingestion must support:

- Team-scoped mailbox connections.
- Provider-specific drivers, starting with Outlook and later Google/Gmail.
- OAuth-based authorization.
- Incremental sync using message IDs, delta tokens, timestamps, or equivalent provider cursors.
- Email sender, recipients, subject, body, received timestamp, and attachment metadata.
- PDF attachments.
- Image attachments.
- HTML receipt bodies.
- Plain text receipt bodies.
- Duplicate detection by message ID, attachment hash, invoice number, receipt number, vendor, amount, and date.

The ingestion pipeline must identify whether an email contains:

- Receipt.
- Invoice.
- Credit memo.
- Refund notice.
- Payment confirmation.
- Subscription renewal notice.
- Non-accounting email.

For receipts and invoices, the system must extract:

- Vendor name.
- Vendor address if available.
- Vendor tax ID if available.
- Customer or billing entity.
- Invoice number.
- Receipt number.
- Order number.
- Confirmation number.
- Transaction reference number.
- Issue date.
- Due date if invoice.
- Payment date if receipt.
- Currency.
- Subtotal.
- Tax.
- Discounts.
- Fees.
- Total.
- Payment method.
- Card last four digits if present.
- Billing period for subscription or usage services.
- Line item descriptions.
- Line item quantities.
- Line item unit prices.
- Line item totals.
- Line item service periods if present.

Every extracted document must be stored as a team-owned document record with the original file or email content preserved, the structured extraction result, AI run metadata, and review state.

## Receipt and Invoice Matching

The platform must match receipts and invoices to bank feed transactions.

Matching signals must include:

- Amount.
- Currency.
- Transaction date.
- Receipt or invoice date.
- Vendor name.
- Vendor aliases.
- Merchant descriptor.
- Card last four digits.
- Bank account or card account.
- Invoice number.
- Receipt number.
- Order number.
- Email received date.
- Payment processor metadata.
- Stripe payout details.
- Historical matching behavior.

Matching results must support:

- One receipt to one transaction.
- One invoice to one transaction.
- One invoice to multiple payments.
- Multiple receipts to one transaction when a bank charge represents a batch.
- One Stripe payout to many charges, refunds, disputes, fees, and net bank deposit.
- Transfer pairing between two bank accounts.
- No receipt required.
- Receipt required but missing.
- Manual upload required.

The application must flag transactions that likely require a receipt or invoice but do not have one. Users need a clear queue showing what documentation is missing and why the system believes it is required.

## Categorization Engine

The categorization engine must assign accounting categories using the team's chart of accounts.

Categorization must consider:

- First-party vendor defaults.
- Historical transactions for the same first-party vendor.
- Similar vendors.
- Similar merchant descriptors.
- Similar line items.
- Similar amounts and billing intervals.
- Existing user-approved categorizations.
- External provider categories.
- Receipt or invoice line item details.
- Bank account source.
- Cardholder or department if provided by Ramp or Brex.
- Stripe fee and payout metadata.
- Transfer detection.

The engine must prefer consistency. If prior approved transactions from a vendor were categorized to a specific account for the same purpose, new similar transactions should use the same account unless the receipt line items or other evidence clearly indicate a different treatment.

Categorization outputs must include:

- Suggested chart of accounts entry.
- Suggested first-party vendor.
- Suggested treatment, such as expense, income, transfer, refund, fee, reimbursement, or owner contribution.
- Confidence score.
- Explanation.
- Similar records used as evidence.
- Whether receipt documentation is required.
- Whether manual review is required.

Users must be able to override suggested categorization. Overrides must be stored as training signals for future categorization in that team.

Categorization must be modeled as auditable history, not as a single overwritten value. The system should distinguish between suggestions, assignments, approvals, overrides, reversals, and current effective categorization.

Categorization history records must support:

- Team ownership.
- Bank transaction.
- Receipt or invoice line item if categorizing at line-item level.
- Suggested or assigned chart of accounts entry.
- Previous effective chart of accounts entry if any.
- New effective chart of accounts entry if approved or overridden.
- Source, such as AI, rule, integration, import, or human.
- Actor attribution for human changes.
- AI run attribution for AI suggestions.
- Similar historical records used as evidence.
- Explanation.
- Confidence.
- Effective current flag.
- Superseded or reversed timestamp.

The frontend must be able to show categorization lineage. If a transaction was categorized as subscriptions and then changed to travel, both states must be visible with attribution, timestamps, and reasoning.

## Manual Review Queue

The product must include a manual review queue for anything that cannot be safely automated.

Items must enter manual review when:

- AI models disagree materially.
- Required extracted fields are missing.
- Categorization confidence is below threshold.
- Vendor match confidence is below threshold.
- More than one transaction match is plausible.
- No transaction match is found for a receipt that appears paid.
- A transaction likely requires a receipt but none exists.
- A transaction appears to be a transfer but no counterpart is found.
- A duplicate receipt, invoice, vendor, or transaction is suspected.
- External provider sync failed.
- A close period contains unresolved transactions.

Review items must show:

- The underlying transaction, receipt, invoice, vendor, or integration event.
- AI suggestions and confidence.
- Conflicting AI outputs if applicable.
- Similar historical records.
- External provider metadata.
- Required user action.
- Available resolution options.

Resolution actions must include:

- Approve suggestion.
- Change vendor.
- Create vendor.
- Merge vendor.
- Split vendor.
- Change chart of accounts entry.
- Mark receipt as not required.
- Upload receipt.
- Match to transaction.
- Unmatch from transaction.
- Mark as transfer.
- Link transfer counterpart.
- Ignore external duplicate.
- Retry integration sync.

Every manual decision must be written to an audit log.

## Transfers Between Accounts

The system must detect and reconcile transfers between accounts.

Transfers may occur between:

- Mercury accounts.
- Ramp payment accounts.
- Brex payment accounts.
- Bank accounts connected through Stripe Financial Connections.
- Bank accounts connected through other direct bank APIs.
- Stripe payout clearing accounts and operating bank accounts.
- Other internal accounts.

Transfer detection must consider:

- Equal and opposite amounts.
- Close posting dates.
- Matching descriptions.
- Shared transfer IDs if provider exposes them.
- Account ownership within the same team.
- Known payment rails.
- Manual user pairing.

Transfers must not be categorized as income or expense. They must be recorded as movements between accounts and must be reconcilable as paired records.

If only one side of a likely transfer is present, the item must remain unresolved until the counterpart is found, imported, or manually marked.

## Stripe Integration

Stripe must be a first-class integration.

The Stripe integration must import:

- Customers as contacts.
- Customer metadata.
- Invoices.
- Invoice line items.
- Payments.
- Charges.
- Refunds.
- Disputes if applicable.
- Balance transactions.
- Payouts.
- Stripe fees.
- Payment method details when available.

Stripe contacts must be team-scoped and linked to first-party contacts in the application.

Stripe invoices must be matched to incoming payments and bank deposits. The matching model must account for:

- Gross invoice amount.
- Stripe processing fees.
- Refunds.
- Disputes.
- Net payout amount.
- Payout timing delays.
- Multiple charges in one payout.

Stripe fees must be categorized separately, for example as merchant processing fees, using the team's chart of accounts.

The platform must prevent double-counting revenue when both Stripe invoices and bank deposits are imported.

## Vendor Integration Driver Model

The codebase must support a clean integration driver model for vendors and financial providers.

Each integration should live in an isolated module or namespace with a consistent contract. Integration-specific API clients, authentication, pagination, sync cursors, response mapping, webhook handling, and error handling must not leak into core accounting logic.

Driver contracts should support capabilities such as:

- Connect.
- Refresh credentials.
- Sync vendors.
- Sync contacts.
- Sync bank accounts.
- Sync transactions.
- Sync invoices.
- Sync receipts.
- Sync documents.
- Fetch invoice by ID.
- Fetch receipt by ID.
- Search invoices.
- Search receipts.
- Push vendor updates if supported.
- Report supported capabilities.

Each driver must declare capability flags, such as:

- Supports bank feed import.
- Supports receipt import.
- Supports invoice import.
- Supports contact import.
- Supports vendor import.
- Supports vendor update.
- Supports webhooks.
- Supports incremental sync.
- Supports file download.
- Supports usage details.

Initial drivers to plan for:

- Outlook.
- Google/Gmail.
- Mercury.
- Ramp.
- Brex.
- Stripe.
- Stripe Financial Connections.
- AWS billing or invoicing.

Vendor-specific integrations, such as AWS invoice retrieval, should use the same driver architecture when possible.

## AWS and Service-Specific Invoice Retrieval

The platform should allow direct integrations with vendors that expose invoice, receipt, billing, or usage APIs.

AWS is an initial target because invoices and usage details may be available through AWS APIs.

The product should maintain a vendor integration opportunity workflow:

- List all first-party vendors by spend volume, transaction count, and missing receipt count.
- Identify vendors likely to have APIs.
- Track whether an integration exists.
- Track whether an integration is planned, unavailable, blocked, or complete.
- Store notes about API documentation, auth requirements, and available invoice or usage endpoints.
- Allow a first-party vendor to be linked to an integration driver once built.

The long-term goal is to reduce manual receipt collection by allowing the application to fetch receipts, invoices, and usage data directly from vendor APIs.

## Contacts

Contacts represent customers, payers, counterparties, or business entities that are not necessarily vendors.

Contacts must be team-scoped.

Stripe customers must import into contacts. Bank feed counterparties may also map to contacts when relevant.

Contacts should support:

- Display name.
- Legal name.
- Email addresses.
- Phone numbers.
- Billing address.
- Shipping address if applicable.
- External mappings.
- Source integration.
- Linked invoices.
- Linked payments.
- Notes.

Contacts must not be globally shared between teams.

## Documents

Documents represent original source files and extracted accounting artifacts.

Documents must be team-scoped and may originate from:

- Email attachments from Outlook, Google/Gmail, or future providers.
- Email bodies from Outlook, Google/Gmail, or future providers.
- Manual uploads.
- Stripe invoice PDFs.
- Provider APIs.
- Vendor-specific integrations.

Documents should support:

- Original filename.
- MIME type.
- File size.
- Content hash.
- Storage path.
- Source provider.
- Source external ID.
- Document type.
- Extracted structured data.
- Linked receipt.
- Linked invoice.
- Linked bank transaction.
- AI extraction runs.
- Review status.

Original documents must be preserved for auditability.

## Reconciliation and Book Closing

The product must provide a workflow to reconcile accounts and close books for a period.

A close period should be team-scoped and represent a month or other accounting period.

A close period must track:

- Start date.
- End date.
- Status.
- Bank accounts included.
- Unreviewed transactions count.
- Missing receipt count.
- Unmatched receipt count.
- Unmatched invoice count.
- Unresolved transfer count.
- Categorization exception count.
- Reconciled transaction count.
- Locked timestamp.
- Closed by user.

A period cannot be closed until required blockers are resolved or explicitly waived with a reason.

Blockers should include:

- Uncategorized transactions.
- Transactions requiring receipts without receipts.
- AI-disputed extraction or categorization results.
- Unmatched receipts or invoices.
- Suspected duplicate transactions.
- Unresolved transfers.
- Failed critical integration syncs.
- Bank account reconciliation differences.

Once a period is closed, changes to records in that period must be restricted. If edits are allowed, they must create audit log entries and potentially reopen the period or create an adjustment workflow.

## Reporting

The product must support reporting based on normalized first-party data.

Initial reports should include:

- Spend by first-party vendor.
- Spend by chart of accounts entry.
- Spend by bank account or card source.
- Missing receipts by vendor.
- Missing receipts by account.
- Categorization consistency exceptions.
- Stripe gross revenue, fees, refunds, disputes, and net payouts.
- Transfers between accounts.
- Close readiness by period.

Reports must use first-party vendors, first-party chart of accounts entries, and team-scoped normalized data rather than raw external provider categories.

## Auditability

The system must maintain an audit trail for every platform action that can affect financial records, source evidence, reconciliation state, user attribution, integration state, or AI-derived decisions. Auditability is not optional and must not be deferred.

Audit log entries should include:

- Team.
- User if human initiated.
- System actor if automated.
- Entity type.
- Entity ID.
- Action.
- Before values.
- After values.
- Reason.
- AI run reference if AI initiated.
- Integration run reference if provider initiated.
- Source document reference if document-derived.
- Actor attribution snapshot.
- Request or job correlation ID if available.
- Timestamp.

Auditable actions include:

- Vendor creation, merge, split, and rename.
- External vendor mapping changes.
- Chart of accounts changes.
- Transaction categorization changes.
- Receipt requirement changes.
- Receipt and invoice matches.
- Transfer pairing.
- Manual review resolutions.
- Close period lock and unlock actions.
- External sync pushes.
- AI suggestion acceptance or rejection.
- AI input creation.
- AI provider response receipt.
- AI consensus pass creation.
- AI consensus finalization.
- User profile attribution snapshot updates.

Audit logs and history records must be surfaced in the frontend wherever they help explain current financial state. A user reviewing a transaction, document, vendor, close period, or AI decision should be able to inspect the relevant timeline without needing database access.

## Data Import, Sync, and Idempotency

All integrations must be idempotent. Re-running a sync must not duplicate transactions, vendors, contacts, invoices, receipts, documents, or mappings.

Each imported record must store enough external identity to support updates:

- Provider.
- External ID.
- External version or updated timestamp if available.
- Sync cursor or batch reference.
- Raw payload.

The system must handle:

- Provider pagination.
- Rate limits.
- Retryable failures.
- Permanent failures.
- Deleted or voided external records.
- Updated external records.
- Duplicate provider records.
- Partial sync completion.

Integration sync jobs must be observable through status records, logs, and review items when action is required.

## Security and Credentials

Integration credentials must be encrypted and team-scoped.

The platform must never expose raw access tokens, refresh tokens, API keys, or secrets in logs, audit records, AI prompts, or UI responses.

AI prompts must not include secrets. When sending documents or extracted text to AI providers through the Laravel AI SDK, only the data required for the task should be sent.

The application must support revoking integration connections.

## Suggested Domain Entities

The implementation should expect domain models similar to the following. Exact names may change to match codebase conventions, but the concepts should remain explicit.

- Team.
- User.
- TeamMembership.
- BankConnection.
- BankAccount.
- BankTransaction.
- Vendor.
- ExternalVendorMapping.
- Contact.
- ExternalContactMapping.
- ChartAccount.
- Document.
- Receipt.
- Invoice.
- InvoiceLineItem.
- TransactionMatch.
- TransactionMatchHistory.
- CategorizationSuggestion.
- CategorizationAssignment.
- CategorizationHistory.
- CategorizationRule.
- AiRun.
- AiModelResult.
- AiConsensusRun.
- AiConsensusAttempt.
- ManualReviewItem.
- IntegrationConnection.
- IntegrationSyncRun.
- ClosePeriod.
- EntityEvent.
- ActorSnapshot.
- AuditLogEntry.

Every accounting entity above must be team-scoped unless there is a specific, documented reason otherwise.

## Initial Build Priorities

The first usable version should focus on closing books, not broad accounting completeness.

Priority order:

1. Team-scoped foundation with users, teams, team switching, and authorization.
2. Immutable audit, event history, actor attribution snapshots, and no-hard-delete record retention.
3. Team-owned chart of accounts with starter accounts.
4. First-party vendor model and external vendor mappings.
5. Bank transaction import foundation with at least one direct provider, Stripe Financial Connections, or manual seed/import path.
6. Email receipt and invoice ingestion, starting with Outlook and designed for Google/Gmail.
7. Laravel AI SDK based extraction for receipts and invoices.
8. AI-assisted transaction categorization using historical consistency.
9. Immutable categorization suggestion and assignment history.
10. Receipt-to-transaction matching.
11. Manual review queue.
12. Reconciliation and close period workflow.
13. Stripe import for contacts, invoices, fees, payouts, and matching.
14. Ramp, Brex, Mercury, Stripe Financial Connections, and provider-specific vendor sync behavior.
15. Vendor integration opportunity tracking.
16. AWS invoice retrieval driver.

## Explicit Non-Goals for Early Versions

The early product does not need to implement:

- Payroll.
- Inventory management.
- Full tax filing.
- Accounts payable approval workflows beyond receipt and invoice tracking.
- Accounts receivable dunning.
- General-purpose CRM.
- Multi-currency remeasurement unless required by imported transactions.
- Public marketplace integrations.
- Full Xero feature parity.

These can be reconsidered only if they directly support closing books.

## Definition of Done for Core Accounting Automation

The platform is successful when a team can:

- Connect financial sources.
- Connect email providers, starting with Outlook and later Google/Gmail.
- Import bank feed transactions.
- Import or detect receipts and invoices.
- Extract receipt and invoice details with the Laravel AI SDK.
- Run multi-model verification for important AI extraction and categorization.
- Preserve every AI input, output, consensus attempt, and final AI-assisted decision.
- Normalize external vendor names into first-party vendors.
- Categorize transactions consistently based on prior approved treatment.
- Show complete categorization history, including prior categories, current category, actor attribution, AI attribution, and timestamps.
- Match receipts and invoices to transactions.
- Identify missing receipts.
- Identify transfers.
- Import Stripe contacts, invoices, fees, payouts, and payments without double-counting.
- Review exceptions in a manual queue.
- Reconcile accounts.
- Close a period with clear blockers, waivers, audit logs, and locked state.

