@php
/** @var \Knuckles\Camel\Output\OutputEndpointData $endpoint */
$hasBody = !empty($endpoint->cleanBodyParameters);
$hasAuth = $endpoint->metadata->authenticated ?? false;
$method = strtoupper($endpoint->httpMethods[0]);
$hasQueryParams = !empty($endpoint->cleanQueryParameters);
@endphp
```javascript
// Next.js (App Router) fetch example
@if($hasAuth)
const getAuthToken = () => {
  // Replace with your token retrieval logic
  return localStorage.getItem('api_token');
};

@endif
const response = await fetch('{{ rtrim($baseUrl, '/') }}/{{ ltrim($endpoint->boundUri, '/') }}', {
  method: '{{ $method }}',
  headers: {
    'Accept': 'application/json',
@if($hasBody)
    'Content-Type': 'application/json',
@endif
@if($hasAuth)
    'Authorization': `Bearer ${getAuthToken()}`,
@endif
  },
@if($hasBody)
  body: JSON.stringify({!! json_encode($endpoint->cleanBodyParameters, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) !!}),
@endif
});

if (!response.ok) {
  const error = await response.json();
  throw new Error(error.message || 'Request failed');
}
@if($method === 'DELETE')

// No content returned (204)
@else

const data = await response.json();
console.log(data);
@endif
```
