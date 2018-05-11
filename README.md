# json-client

A simple client for JSON APIs using Guzzle and the Symfony 
Serializer.

To implement a API client, you would extend AbstractApiClient 
and write your methods, using the Guzzle Http Client to transmit.


```PHP
class MyClient extends AbstractApiClient {
    
    
    /**
     * @throws ServerMessageException
     * @throws UnexpectedResponseException
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
     * @throws ServerMessageException
     * @throws UnexpectedResponseException
     * @throws TransferException
     * @returns Model 
     */
    public function get(int $id):Model
    {
        $response = $this->http->get('model/'.$id);
        return $this->deserializeResponse($response, Model::class);
    }


}
```

So the implementation is up to you. 

If you want more magic, have a look at [guzzle/command](https://github.com/guzzle/command).

The abstract base class just adds some middleware to automatically 
serialize data and to detect server error messages in JSON format.
