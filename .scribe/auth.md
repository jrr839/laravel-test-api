# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {YOUR_TOKEN_HERE}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

You can retrieve your API token by logging in via <code>POST /api/auth/login</code> or registering via <code>POST /api/auth/register</code>. The token should be sent in the <code>Authorization</code> header with the <code>Bearer</code> prefix.
