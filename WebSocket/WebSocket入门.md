# 为什么需要WebSocket？
虽然有HTTP协议，但是一个很明显的缺点是：所有请求只能有客户端发起，向服务端请求。而服务端有任何状态变化，无法直接通知到客户端。简单处理的方法就是`轮询`，连续不断发起请求，但是这个非常浪费资源，因为需要不断请求连接。最常见的例子就是聊天室。  

# WebSocket
优点：
- 支持双向通信，实时性更强  
- 更好的支持二进制  
- 较少的控制开销，数据交换时，数据包请求头较小
- 支持更多扩展

websocket也是通过http请求去建立连接，请求格式如下：
```bash
GET ws://localhost:3000/ws/chat HTTP/1.1
Host: localhost
Upgrade: websocket
Connection: Upgrade
Origin: http://localhost:3000
Sec-WebSocket-Key: client-random-string
Sec-WebSocket-Version: 13
```
跟普通http请求的区别：  
- GET请求的地址不是类似`/path/`，而是以`ws://`开头的地址  
- 请求头`Upgrade: websocket`和`Connection: Upgrade`表示这个连接将要被转换为WebSocket连接  
- `Sec-WebSocket-Key`是用于标识这个连接，并非用于加密数据  
- `Sec-WebSocket-Version`指定了WebSocket的协议版本  
- 协议标识符是`ws`（如果加密，则为`wss`），服务器网址就是 URL。  

服务器返回数据：  
```bash
HTTP/1.1 101 Switching Protocols
Upgrade: websocket
Connection: Upgrade
Sec-WebSocket-Accept: server-random-string
```
该响应代码`101`表示本次连接的HTTP协议即将被更改，更改后的协议就是`Upgrade: websocket`指定的WebSocket协议  

成功建立连接后，客户端和服务端就可以直接主动发消息给对方。消息传递的格式有两种：文本，二进制数据.通常可以发送JSON数据，方便处理  

## WebSocket对象
```JS
var ws = new WebSokcet(url, [protocol])

const ws = new WebSocket('ws://echo.websocket.org', ['myProtocol1', 'myProtocol2'])
```
WebSocket 构造函数可接受两个参数，其中，第一个参数必须是以 `ws://` 或 `wss://` 开头的完全限定的 URL  
第二个为非必要参数，用于指定可接受的子协议，有两种可能的类型:  
- String 类型，值为客户端和服务器端均能理解的协议  
- Arrary 类型，包含一组客户端支持的协议（String 类型）  

## WebSocket 属性
### Socket.readyState
只读属性 `readyState` 表示连接状态，可以是以下值：
- 0 | WebSocket.CONNECTING：表示连接尚未建立  
- 1 | WebSocket.OPEN：表示连接已经建立  
- 2 | WebSocket.CLOSEING：表示连接正在关闭  
- 3 | WebSocket.CLOSED: 表示连接已经关闭或者连接不能打开  

### bufferedAmount
WebSocket 对象的 `bufferedAmount` 属性可以用检查已经进入发送队列，但是还未发送到服务器的字节数。可以用来判断发送是否结束    

### protocol
WebSocket 对象的 `protocol` 属性值为 WebSocket 打开连接握手期间，服务器端所选择的`协议名`  

protocol 属性在最初的握手完成之前为空，如果服务器没有选择客户端提供的某个协议，则该属性保持空值

## WebSockets事件处理
WebSocket 对象具有以下 4 个事件：  

### open 事件
当服务器响应了 WebSocket 连接请求，触发`open`事件并建立一个连接，此时WebSocket已经准备好发送和接收数据，open事件对应的回调函数是`onopen()`  
```JS
ws.onopen = (event) => {
    console.log('开启连接');
}

// 或者
ws.addEventListener('open', (event) => {
    console.log('开启连接');
}, false)
```

### message事件
`message`事件在接收到消息是触发，消息内容存储在事件对象`event`的`data`中，对应的回调函数是`onmessage()`  
```JS
ws.onmessage = (event) => {
    if (typeof event.data === 'string') {
        console.log('接收到的string消息内容为：' + event.data)
    } else {
        console.log('其他类型消息')
    }
}
```
除了普通文件，WebSocket消息内容还可以是二进制，这种数据作为`Blob`消息或者`ArraryBuffer`消息处理。暂不赘述。  

### error事件
`error`事件在响应意外发生故障时触发，对应的回调函数是`onerror()`。错误会导致WebSocket连接关闭。  

### close事件
`close`事件在连接关闭时触发，对应的回调函数是`onclose()`。一旦连接关闭，客户端和服务器端不在接续接收和发送消息。  
`close`事件的3个常用属性：  
- `wasClean`：布尔值，表示连接是否被正确关闭。如果是来自服务器的close帧的响应，则为true；如果是因为其他原因关闭，则为false  
- `code`：服务器发送的关闭连接握手状态码  
- `reason`：服务器发送的关闭连接握手状态  

## WebSocket方法
WebSocket API提供两个方法供调用。

### send()  
使用`send()`方法可以从客户端向服务端发送消息。前提是必须当WebSocket在客户端和服务端建立全双工双向连接后，才可以调用该方法。所以一般是在`open`事件触发之后，`close`触发之前调用`send()`发送消息  
```JS
ws.onopen = (event) {
    ws.send('hello websocket');
}
```

### close()
通过使用`close()`方法，可以人为的手动关闭WebSocket连接或者终止连接尝试。如果连接已关闭，则该方法什么也不做  

可以向`close()`方法传递两个参数：  
- `code`：Number类型，状态代码  
- `reason`: String类型，文本字符串，传递一些关于关闭连接的信息  


> 参考链接：  
> [《WebSocket 教程 - 阮一峰》](http://www.ruanyifeng.com/blog/2017/05/websocket.html)  
> [《WebSocket客户端编程》](https://lfkid.github.io/2016/11/29/WebSocket%E5%AE%A2%E6%88%B7%E7%AB%AF%E7%BC%96%E7%A8%8B/)