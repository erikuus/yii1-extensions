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
  reference it is false, even if funds were captured.
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
live-tested. Capture handling is unchanged: callers must serialize capture,
persist its distinct reference and reconcile uncertain outcomes using GET before
considering another write. Application completion deduplication remains its own concern.
This version verifies card payments and one full capture. Bank payments, partial
captures, cancellations/refunds, subscriptions and application fulfilment are outside
this implementation. Reversed or partially captured payments do not meet its
full-capture predicate. Application state must not undo previously confirmed
fulfilment merely because a later read no longer meets that predicate.
Production endpoint selection is covered by fake transport only; production
integration and merchant acceptance remain deployment work.

## Callback action

Register `ext.components.swedbankpay.XSwedbankPayWebhookAction` when the application
is ready. Configure `componentName` (default `swedbankPay`) and two public methods:

1. `bindingCallback($orderId)` returns the application's saved `orderId`,
   `reference`, integer `amount`, `currency`, and optional `captureReference`;
   return null for an unknown order.
2. `verifiedCallback($evidence)` receives bound GET evidence, never the notification's
   asserted result. Return exactly true only after durable handling. Make handling
   idempotent and safe for delayed or duplicate notifications.

Only POST JSON with `paymentOrder.id` is accepted. Malformed requests return 400,
other methods 405, unknown bindings 404, and configuration/read/handler failures
500. Successful handoff returns 200. There is no success-versus-failure dispatch
based on Paid alone, automatic capture, or application model dependency.
Routing, endpoint CSRF policy, rate limiting and business processing belong to the
integrating application. No VAU action is registered in milestone 1.

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
