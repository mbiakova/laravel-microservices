# Example: two applications and their foundation

```
foundation/   example/foundation, required by both: what billing shares
              Billing/Contracts/BillingService   Billing/Services/BillingRpcService   Billing/Shadows/CustomerShadow
billing/      answers BillingService; its Customer model is the source of a copy
orders/       calls BillingService over RPC; keeps billing's customers in orders_customers
```

```bash
./run.sh   # needs PHP 8.4, Composer and Redis on localhost
```

It installs both applications, starts billing on `127.0.0.1:8001` and the event consumer of
orders, then creates a customer in billing. Orders prints it twice: from its own copy, filled by the
event, and from billing, over RPC.

```
Customer 1 created.
copy: Ada
rpc: Ada
```
