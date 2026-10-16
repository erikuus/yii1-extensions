# XSwedbankPay

Yii 1.1 Checkout v3.1 component and callback action. PHP 5.6 compatible.
Starts disabled (`enabled=false`) with sandbox endpoints (`testMode=true`).
Registration and initialization make no requests. In Yii configuration set
`allowRequests` to boolean `true` to permit API calls. Yii consumes its reserved
`enabled` option before constructing the component; `enabled=true` alone does
not opt this client in.

## Application submission

Register this component directly, as with XEveryPay/XStripe; no application
subclass is needed. Supply a `paymentModel` Yii alias, then set `payment`,
`productName`, `language`, `returnUrl`, `cancelUrl`, `termsOfServiceUrl` and `logoUrl`.
Set `callbackUrl` when the application callback is registered; this affects new
orders only and never patches an existing provider order.
`submitPayment()` loads the binding, creates or recovers the order if needed,
verifies the checkout URL, sets `paymentLink` and redirects if `autoRedirect=true`.

The configured model implements:

- Static `forPayment($payment, $client)`: save/load a committed binding, check
  application/environment rules and return an object with `reference`, `amount`
  (minor units), `currency` and nullable `order_id`.
- Instance `saveOrder($orderId)`: persist the resource ID and update `order_id` on
  that object. Concurrent saves of the same ID are allowed; replacing an existing
  different binding must fail. Never persist bearer checkout URLs.

In VAU use `paymentModel=application.models.SwedbankPayPayment`. Controllers save
Payment normally before submission. The extension knows no VAU models, business
relations or tables. A new local payment has a new reference; an explicit retry
of the same saved payment keeps its reference and expected amount/currency/merchant.
The low-level API is also usable without a configured model.

## Application confirmation

The browser controller and webhook action set `payment`, then call `validatePayment()`.
The webhook supplies the resolved expected binding as an optional argument; it must
match the model's freshly loaded `binding` array before any provider IO. Browser
validation uses the saved payment directly and needs no argument.
The return contract extends the existing boolean validation with a nonterminal
null result:

| Return | `validationResult` | Application handling |
| --- | --- | --- |
| true | `captured` | Full capture is verified and saved; enter the application's existing completion flow. |
| false | `failed` | Verified terminal failure; use the application's normal failure handling. |
| null | `pending` | Leave payment status unchanged and show the pending message. |
| null | `error` | Confirmation is uncertain; leave payment status unchanged and retry verification later. |

The result and safe `errorMessage` key reset on every validation and exception.
Callers must test null explicitly before their success/failure branches; false and
null must not be conflated. `isAutoRequest()` returns false because notifications
use the separate webhook action. Controllers retain messages, redirects and
business calls. There is no application coordinator, completion owner or receipt API.
`confirmPayment()` is the underlying confirmation operation and throws on errors;
`validatePayment()` converts those errors to null and `validationResult=error`.
The configured model additionally implements:

- Static `forConfirmation($payment, $client)`: load and check an existing binding,
  including `capture_reference`, without opening a transaction around provider IO.
  Its `binding` array contains `orderId`, `reference`, integer `amount`, `currency`
  and `captureReference`.
- `claimCapture()`: commit a conditional capture-intent claim; exactly one caller
  receives true. A requested operation must not expire or be blindly reclaimed.
- `saveConfirmation($evidence)`: durably save capture evidence and prevent delayed
  state regression. Return `pending`, `failed` or `captured`. Do not change the
  application's parent payment status or claim business completion.

`confirmPayment()` obtains new bound GET evidence, captures a wholly uncaptured
card authorization only after the intent claim, and verifies capture independently.
After an uncertain response or a failed local save, subsequent confirmation is
GET-only: verified capture can recover persistence, but an outstanding intent is
never automatically reset. It uses the configured model, with no application subclass.

`isUncapturedAuthorization()` supplies the same predicate to both confirmation
before the intent claim and `captureOrder()` on fresh evidence after the claim.
Both checks remain. The final order/payee read for balances, VAT and the advertised
operation still precedes POST, followed by independent GET verification.
`verifyCapture()` uses `paymentEvidence()` with the capture reference and requires
its `captureVerified` result. It retains upfront reference validation and the same
exception for unverified capture; request order and pending/retry handling are unchanged.

VAU uses its existing validated-status/`PaymentRequest` latch for all providers.
Capture persistence leaves the parent status untouched so it cannot cause the
legacy already-validated shortcut to skip business work. The application commits
its status/latch before business IO. Exceptions or interruptions after that point
require manual investigation; repeated delivery skips already-validated payments.
There is no separate business/accounting receipt check or automatic recovery here.

## Component contract

Configure `class=ext.components.swedbankpay.XSwedbankPay`, `payeeId` and
`accessToken` from the application's secret mechanism. Enable explicitly.

- `createOrder($reference, $amount, $options)`: amount is integer minor units.
  Options require `description`, actual payer `userAgent`, integer `vatAmount`,
  and `urls` with `hostUrls` and `completeUrl`. Optional: `currency` (EUR),
  `language` (et-EE), and `urls.cancelUrl` / `urls.callbackUrl` /
  `urls.termsOfServiceUrl` / `urls.logoUrl`. All URLs require absolute HTTPS.
  Checkout does not restrict instruments; the merchant account determines available methods. Capture verification still covers cards only.
  Supply a callback URL only after the receiving endpoint works. Save the returned
  order ID against the local attempt. Raw responses and checkout URLs may contain
  tokens and must not be logged.
- `validateCreate($reference, $amount, $options)`: run the same access/input checks
  without network IO.
- `readOrder($id, $reference, $amount, $currency='EUR')`: GET the saved order
  and payee resource; reject ID, payee, reference, amount or currency mismatches.
- `checkoutUrl($id, $reference, $amount, $currency='EUR')`: return the advertised
  hosted redirect for an initialized, bound order. The caller performs the redirect.
- `paymentEvidence($id, $reference, $amount, $currency='EUR', $captureReference=null)`:
  GET sanitized evidence. Paid/Authorization is not capture. `captureVerified`
  requires the persisted capture reference, exactly one matching full capture,
  full reversible balance and no contradictory remaining balance. Without a capture
  reference it is false, even if funds were captured. The list may also contain one
  original Authorization matching the paid transaction number, reference and amount.
  A malformed or unrelated entry, duplicate capture, cancellation or reversal fails
  verification. An Initialized order's id-only empty resource is pending evidence
  only; Paid still requires an explicit financial list.
- `captureOrder($id, $reference, $amount, $captureReference, $currency='EUR', $vatAmount=0)`:
  one full card capture from a wholly uncaptured authorization, followed by independent
  reconciliation. Use a distinct, saved operation reference.
- `verifyCapture($id, $reference, $amount, $captureReference, $currency='EUR')`:
  GET-only reconciliation; throws if full capture cannot be confirmed.

References use 1–30 alphanumeric characters (provider-managed settlement).
Persist the reference and expected binding before creation. There is no automatic
POST retry loop. If creation's response is lost, explicitly retry with the same
reference and binding. The sandbox returns HTTP 409 with a
`ConflictingPaymentOrderId` UUID. For that specific creation conflict, `createOrder()`
GET-verifies the original ID, merchant, reference, amount and currency before
returning it. A changed amount fails. An unrecognized conflict remains an error.
With a saved ID, submission uses GET only; checkout still requires Initialized.

This was verified on 2026-10-16 against two fresh sandbox references, including
discarding a received 201 and retrying. It is creation recovery, not Stripe-style
response replay or a general safe-write-retry rule. Production behavior is not
live-tested. Low-level capture callers must serialize capture, persist its distinct
reference and reconcile uncertain outcomes using GET before considering another
write. The configured-model confirmation API above enforces those requirements
without owning application completion.
This version verifies card payments and one full capture. Bank payments, partial
captures, cancellations/refunds, subscriptions and application fulfilment are outside
this implementation. Reversed or partially captured payments do not meet its
full-capture predicate. Application state must not undo previously confirmed
fulfilment merely because a later read no longer meets that predicate.
Production endpoint selection is covered by fake transport only; production
integration and merchant acceptance remain deployment work.

## Callback action

Register `ext.components.swedbankpay.XSwedbankPayWebhookAction` when the application
is ready. Configure `componentName` (default `swedbankPay`) and three public methods:

1. `bindingCallback($orderId)` returns the application's saved `orderId`,
   `reference`, integer `amount`, `currency`, `captureReference`, and local `payment`
   object; return null for an unknown order. Use the model's `binding` array and
   append the payment object. Do not derive any expectation from notification data.
2. `successCallback($binding, $webhookUid)` receives the saved binding without the
   payment object, only after the component verifies and persists full capture.
   Return exactly true after durable handling; other returns cause HTTP 500.
   Make handling idempotent and safe for delayed or duplicate notifications.
3. `failureCallback($message, $webhookUid)` receives a safe message for verified
   terminal failure only. It may return void; an exception causes HTTP 500.

Like EveryPay, configure `log` (default false), `logCategory` (default the action's
class category), and optionally `logLevel` (default error). Action retry diagnostics
contain a generated UID and a generic message, never notification bodies, raw
provider payloads, credentials or exception text. Application callback logging is
separate, following the existing controller policy.

Only POST JSON with `paymentOrder.id` is accepted. Malformed requests return 400,
other methods 405, unknown bindings 404, and configuration/read/handler failures
500. The action calls `validatePayment($binding)` once before dispatch. Authorization
alone cannot reach success: the component owns capture and its committed intent.
Pending returns 200 without either result callback; uncertain validation returns
500 for retry. Verified failure and successful handling return 200. There is no
extra provider read or validation inside the application's success handler.
Routing, endpoint CSRF policy, rate limiting and business processing belong to the
integrating application. VAU now registers the action and acknowledges durable
pending/failed evidence and captured payments handled through its legacy latch.
Uncertain capture, read/save errors and latch acquisition failures return 500.
Business-handler exceptions after the latch are logged and acknowledged, as in
the legacy policy; further delivery does not automatically retry business work.

Provider references:
[callback contract](https://developer.swedbankpay.com/checkout-v3/features/payment-operations/callback/),
[payment requests](https://developer.swedbankpay.com/checkout-v3/get-started/payment-request/),
[operations](https://developer.swedbankpay.com/checkout-v3/technical-reference/operations/).

## Isolated verification

`tests/XSwedbankPayTest.php` runs with PHPUnit 5.7 and `--no-configuration`.
It loads Yii classes without creating an application, database or service clients.
Set `YII_FRAMEWORK` to `yii.php` when the sibling installation is elsewhere.
Example from VAU:

```text
php protected/tests/phpunit.phar --no-configuration C:/inetpub/yii/extensions/components/swedbankpay/tests/XSwedbankPayTest.php
```

`tests/verify-sandbox.php` is a GET-only check of an existing fully captured sandbox
order. Pass a JSON line through private stdin: `payeeId`, `accessToken`,
`orderId`, `reference`, integer `amount`, and `captureReference`.
Never put credentials in command arguments or repository files. Its transport guard
rejects every write and every non-sandbox request.
