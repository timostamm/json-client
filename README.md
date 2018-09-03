# json-client

A simple client for JSON APIs using Guzzle and the Symfony 
Serializer.

To implement a API client, you can extend AbstractApiClient 
and write your methods, using the Guzzle Http Client to transmit.


```PHP
class MyClient extends AbstractApiClient {
    
    
    /**
     * @throws TransferException
     */
    public function send(Model $model):void
    {
        // The data will automatically be 
        // serialized to JSON. 
        $this->http->post('model', [
            'data' => $model
        ]);
    }
    
    
    /**
     * @param int $id
     * @throws TransferException
     * @returns Model 
     */
    public function get(int $id):Model
    {
        return $this->http->get('model/'.$id, [
            'deserialize_to' => Model::class
        ]);
    }


}
```

All functionality is implemented as middleware, the 
`AbstractApiClient` just configures the Guzzle `HandlerStack` for you. 



### Provided middleware 


#### Serialization

See `DeserializeResponseMiddleware` and `SerializeRequestBodyMiddleware`.


#### Server error messages

`ServerMessageMiddleware` provides support for JSON error messages. 


#### Response expectations

If you want to make sure that a response has a specific header, content 
type or other feature, use `ResponseExpectationMiddleware`. 


#### Logging

There is also middleware to log all HTTP requests (and corresponding 
response or exception), see `HttpLoggingMiddleware` 

An adapter for `Psr\Log\LoggerInterface` is available.

This middleware is not added by default because the order is 
important: The `HttpLoggingMiddleware` must be added last.
