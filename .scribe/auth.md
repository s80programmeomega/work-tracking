# Authenticating requests

To authenticate requests, include an **`Authorization`** header with the value **`"Bearer {VOTRE_TOKEN_SANCTUM}"`**.

All authenticated endpoints are marked with a `requires authentication` badge in the documentation below.

Obtenez votre token via <code>POST /api/auth/login</code>. Passez-le comme <code>Authorization: Bearer {token}</code>.
