# XSwedbankPay

Yii 1.1 Checkout v3.1 component and callback action. PHP 5.6 compatible.
Starts disabled (`enabled=false`) with sandbox endpoints (`testMode=true`).
Registration and initialization make no requests.

## Component contract

Configure `class=ext.components.swedbankpay.XSwedbankPay`, `payeeId` and
`accessToken` from the application's secret mechanism. Enable explicitly.

- `createOrder($reference, $amount, $options)`: amount is integer minor units.
  Options require `description`, actual payer `userAgent`, integer `vatAmount`,
  and `urls` with `hostUrls` and `completeUrl`. Optional: `currency` (EUR),
  `language` (et-EE), and `urls.cancelUrl` / `urls.callbackUrl`.
  Supply a callback URL only after the receiving endpoint works. Save the returned
  order ID against the local attempt. Raw responses and checkout URLs may contain
  tokens and must not be logged.
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
The caller owns persistent attempt binding, serialization, capture intent and
recovery. Persist intent before POST; no automatic write retries are implemented.
After a timeout or any post-send verification error, reconcile before deciding
whether a write is safe. The component cannot prevent concurrent callers.

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
