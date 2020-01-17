# cURL请求类

- `get($url, $httpCode)` url get请求
- `post($url, $httpCode, $data, $header)` url post请求
- `put($url, $httpCode, $data, $header)` url put请求
- `request($method, $url, $httpCode, $data, $header)` url request请求
- `timeOut($second)` 设置响应超时时间(可链式调用)
- `useCert($certPath, $keyPath)` 设置使用证书参数(可链式调用)