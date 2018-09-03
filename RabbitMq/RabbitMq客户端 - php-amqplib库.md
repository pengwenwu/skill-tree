
> 官网教程地址：http://www.rabbitmq.com/tutorials/tutorial-one-php.html  
## Hello World!简单使用 
### 安装
直接使用composer加载 
```bash
composer require php-amqplib/php-amqplib
```

### send发送
在`send.php`中包含库并使用： 
```php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
```

创建到服务器的连接： 
```php
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
```

要发送，我们必须声明一个队列供我们发送; 然后我们可以向队列发布消息： 
```php
$channel->queue_declare('hello', false, false, false, false);
$msg = new AMQPMessage('Hello World!');
$channel->basic_publish($msg, '', 'hello');
echo " [x] Sent 'Hello World!'\n";
```
声明队列是幂等的 - 只有在它不存在的情况下才会创建它。 

关闭了频道和连接: 
```php
$channel->close();
$connection->close();
```
> php send.php执行失败，可能是未安装php的`bcmath`扩展，可以用过phpize动态编译安装 

### receive接收
`receive.php` 

设置与send生产者相同; 我们打开一个连接和一个通道，并声明我们将要消耗的队列。请注意，这与发送的队列匹配。
```php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->queue_declare('hello', false, false, false, false);
echo " [*] Waiting for messages. To exit press CTRL+C\n";
```

定义一个`PHP callable`，它将接收服务器发送的消息。请记住，消息是从服务器异步发送到客户端的。 
```php
$callback = function ($msg) {
    echo ' [x] Received ', $msg->body, "\n";
};
$channel->basic_consume('hello', '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
```
这里通过while保证进程常驻 

- 列出队列
```bash
rabbitmqctl list_queues
```

完整代码：[send.php](https://github.com/rabbitmq/rabbitmq-tutorials/blob/master/php/send.php) 、 [receive.php](https://github.com/rabbitmq/rabbitmq-tutorials/blob/master/php/receive.php) 。 
测试结果如图： 
![hello world](http://pic.pwwtest.com/hello-world-rabbitmq.png) 

## Work queues工作队列
这里将创建一个工作队列，用于在多个工作人员之间分配耗时的任务。 

### 准备
这里将通过`sleep()`函数，模拟耗时任务。通过字符串中`.`点的个数作为其复杂性。 

稍微修改前一个示例中的send.php代码，以允许从命令行发送任意消息。重命名为`new_task.php`： 
```php
$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, '', 'hello');

echo ' [x] Sent ', $data, "\n";
```

旧的receive.php脚本还需要进行一些更改：它需要为消息体中的每个点伪造一秒钟的工作。它将从队列中弹出消息并执行任务，所以我们称之为`worker.php`： 
```php
$callback = function ($msg) {
  echo ' [x] Received ', $msg->body, "\n";
  sleep(substr_count($msg->body, '.'));
  echo " [x] Done\n";
};

$channel->basic_consume('hello', '', false, true, false, false, $callback);
```

### 循环调度
使用任务队列的一个优点是能够轻松地并行工作。如果我们正在积压工作积压，我们可以添加更多工人，这样就可以轻松扩展。 

打开四个控制台。三个将运行worker.php 脚本。测试结果如图： 
![worker](http://pic.pwwtest.com/work-queue-rabbitmq.png) 

默认情况下，RabbitMQ将`按顺序`将每条消息发送给下一个消费者。平均而言，每个消费者将获得相同数量的消息。这种分发消息的方式称为`循环法`。 
### 消息确认
执行任务可能需要几秒钟。您可能想知道如果其中一个消费者开始执行长任务并且仅在部分完成时死亡会发生什么。使用我们当前的代码，一旦RabbitMQ向客户发送消息，它立即将其标记为删除。在这种情况下，如果你杀死一个工人，我们将丢失它刚刚处理的消息。我们还将丢失分发给这个特定工作者但尚未处理的所有消息。 

为了确保消息永不丢失，RabbitMQ支持 消息确认。消费者发回ack（nowledgement）告诉RabbitMQ已收到，处理了特定消息，RabbitMQ可以自由删除它。 

如果消费者死亡（其通道关闭，连接关闭或TCP连接丢失）而不发送确认，RabbitMQ将理解消息未完全处理并将重新排队。如果其他消费者同时在线，则会迅速将其重新发送给其他消费者。 

默认情况下，消息确认已关闭。现在是时候通过设置`第四个参数`来打开它们`basic_consume`到`false`（true表示没有ACK） 
```php
$callback = function ($msg) {
  echo ' [x] Received ', $msg->body, "\n";
  sleep(substr_count($msg->body, '.'));
  echo " [x] Done\n";
  $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};

$channel->basic_consume('task_queue', '', false, false, false, false, $callback);
```

> **被遗忘的ack** 
> 错过ack是一个常见的错误。这是一个简单的错误，但后果是严重的。当您的客户端退出时，消息将被重新传递（这可能看起来像随机重新传递），但RabbitMQ将会占用越来越多的内存，因为它无法释放任何未经处理的消息。 
> 
>  可以使用`rabbitmqctl` 来打印`messages_unacknowledged`字段： 
> ```bash
> sudo rabbitmqctl list_queues name messages_ready messages_unacknowledged
> ```
>  

### 消息持久性 
消息确认确保即使消费者死亡，任务也不会丢失。但是如果RabbitMQ服务器停止，我们的任务仍然会丢失。 

当RabbitMQ退出或崩溃时，它将忘记队列和消息，除非你告诉它不要。确保消息不会丢失需要做两件事：我们需要`将队列和消息都标记为持久`。 

首先，我们需要确保RabbitMQ永远不会丢失我们的队列。为此，我们需要声明它是持久的。为此，我们将`第三个参数`传递给queue_declare为`true`: 
```php
$ channel->queue_declare（'hello'，false，true，false，false）;
```

虽然此命令本身是正确的，但它在我们当前的设置中不起作用。那是因为我们已经定义了一个名为hello的队列 ，这个队列不耐用。RabbitMQ不允许您使用不同的参数重新定义现有队列，并将向尝试执行此操作的任何程序返回错误。但是有一个快速的解决方法 - 让我们声明一个具有不同名称的队列，例如task_queue： 
```php
$channel->queue_declare('task_queue', false, true, false, false);
```
此标志设置为true`需要应用于生产者和消费者`代码。 

此时我们确信即使RabbitMQ重新启动，task_queue队列也不会丢失。现在我们需要`将消息标记为持久性` - 通过设置`delivery_mode = 2`消息属性，AMQPMessage将其作为属性数组的一部分。 
```php
$msg = new AMQPMessage(
    $data,
    array('delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT)
);
```

### 公平派遣
您可能已经注意到调度仍然无法完全按照我们的意愿运行。例如，在有两个工人的情况下，当所有奇怪的消息都很重，甚至消息很轻时，一个工人将经常忙碌而另一个工作人员几乎不会做任何工作。好吧，RabbitMQ对此一无所知，仍然会均匀地发送消息。 

发生这种情况是因为RabbitMQ只是在消息进入队列时调度消息。它不会查看消费者未确认消息的数量。它只是盲目地向第n个消费者发送每个第n个消息。 

我们可以使用`basic_qos`方法和`prefetch_count = 1`设置。这告诉RabbitMQ不要一次向一个worker发送一条消息。或者，换句话说，在处理并确认前一个消息之前，不要向worker发送新消息。相反，它会将它发送给下一个仍然不忙的worker。 
```php
$channel->basic_qos(null, 1, null);
```
完整代码：[new_task.php](https://github.com/rabbitmq/rabbitmq-tutorials/blob/master/php/new_task.php)，[worker.php](https://github.com/rabbitmq/rabbitmq-tutorials/blob/master/php/worker.php) 

测试结果如图： 
![公平差遣](http://pic.pwwtest.com/%E5%B7%A5%E4%BD%9C%E9%98%9F%E5%88%97-%E5%85%AC%E5%B9%B3%E6%B4%BE%E9%81%A3-ranbbitmq.png) 

## Publish/Subscribe（发布/订阅）
工作队列背后的假设是每个任务都交付给一个工作者。在这一部分，我们将做一些完全不同的事情 - 我们将向多个消费者传递信息。此模式称为“发布/订阅”。 

### 交换器
前面教程中的内容： 
- `生产者`是发送消息的用户的应用程序 
- `队列`是存储消息的缓冲器 
- `消费者`是接收消息的用户的应用程序 

RabbitMQ中消息传递模型的`核心思想`是`生产者永远不会将任何消息直接发送到队列`。实际上，生产者通常甚至不知道消息是否会被传递到任何队列。 

相反，生产者只能向`交换器`发送消息。交换是一件非常简单的事情。一方面，它接收来自生产者的消息，另一方面将它们推送到队列。交换器必须确切知道如何处理收到的消息。它应该附加到特定队列吗？它应该附加到许多队列吗？或者它应该被丢弃。其规则由`交换类型`定义。 
![交换器](http://pic.pwwtest.com/%E4%BA%A4%E6%8D%A2%E5%99%A8.png) 

有几种交换类型可供选择：`direct（直接）`，`topic（主题）`，`headers（标题）`和`fanout（扇出）`。我们将专注于最后一个 - fanout扇出。让我们创建一个这种类型的交换，并将其称为日志： 
```php
$channel->exchange_declare('logs', 'fanout', false, false, false);
```

> **列出清单** 
> ```bash
> rabbitmqctl list_exchanges
> ```
> 在此列表中将有一些amq.*交换和默认（未命名）交换。这些是默认创建的。 
>
> **默认交换** 
> 之前能发送消息，是因为我们使用的`默认交换`，通过空字符串`""`来识别 
>  
> 之前是这样发送消息的： 
> ```php
> $channel->basic_publish($msg, '', 'hello');
> ```
>  
> 这里我们使用默认或`无名交换`：消息被路由到具有routing_key指定的名称的队列（如果存在）。路由键是basic_publish的第三个参数 

### 临时队列
能够命名队列对我们来说至关重要 - 我们需要将工作人员指向同一个队列。当您想要在生产者和消费者之间共享队列时，为队列命名很重要。 

但我们的记录器并非如此。我们希望了解所有日志消息，而不仅仅是它们的一部分。我们也只对目前流动的消息感兴趣，而不是旧消息。要解决这个问题，我们需要两件事。 

首先，每当我们连接到Rabbit时，我们都需要一个新的`空队列`。为此，我们可以使用随机名称创建队列，或者更好 - 让服务器为我们选择随机队列名称。 

其次，一旦我们断开消费者，就应该自动删除队列。 

在php-amqplib客户端中，当我们将队列名称作为空字符串提供时，我们使用生成的名称创建一个非持久队列： 
```php
list($queue_name, ,) = $channel->queue_declare("");
```
方法返回时，$queue_name变量包含RabbitMQ生成的随机队列名称。例如，它可能看起来像amq.gen-JzTY20BRgKO-HjmUJj0wLg。 

当声明它的连接关闭时，队列将被删除，因为它被声明为独占。 

### 绑定
![绑定](http://pic.pwwtest.com/%E7%BB%91%E5%AE%9A-%E8%AE%A2%E9%98%85-RabbitMq.png) 

我们已经创建了一个扇出交换和一个队列。现在我们需要告诉交换机将消息发送到我们的队列。交换和队列之间的关系称为绑定。 
```php
$channel->queue_bind($queue_name, 'logs');
```

> **列出绑定** 
> ```bash
> rabbitmqctl list_bindings
> ```
> 

生成日志消息的生产者程序与前一个教程没有太大的不同。最重要的变化是我们现在想要将消息发布到我们的日志交换而不是无名交换。这里是emit_log.php脚本的代码 ： 
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();

$channel->exchange_declare('logs', 'fanout', false, false, false);

$data = implode(' ', array_slice($argv, 1));
if (empty($data)) {
    $data = "info: Hello World!";
}
$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'logs');

echo ' [x] Sent ', $data, "\n";

$channel->close();
$connection->close();
```

在建立连接后我们宣布了交换。此步骤是必要的，因为禁止发布到不存在的交换 

receive_logs.php的代码：
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';
use PhpAmqpLib\Connection\AMQPStreamConnection;
$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare('logs', 'fanout', false, false, false);
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$channel->queue_bind($queue_name, 'logs');
echo " [*] Waiting for logs. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
```

## Routing路由
这里我们将为其添加一个功能 - 我们将只能订阅一部分消息。例如，我们只能将关键错误消息定向到日志文件（以节省磁盘空间），同时仍然能够在控制台上打印所有日志消息。 

### 绑定
之前绑定流程： 
```php
$channel->queue_bind($queue_name, 'logs');
```
绑定是交换和队列之间的关系。这可以简单地理解为：队列对来自此交换的消息感兴趣。 

绑定可以采用额外的`routing_key`参数。为了避免与$ channel::basic_publish参数混淆，我们将其称为`绑定密钥`。这就是我们如何使用键创建绑定： 
```php
$binding_key = 'black';
$channel->queue_bind($queue_name, $exchange_name, $binding_key);
```
绑定密钥的含义取决于交换类型。我们之前使用的扇出交换只是忽略了它的价值。 

### 直接交换
我们上一个教程中的日志记录系统向所有消费者广播所有消息。我们希望扩展它以允许根据消息的严重性过滤消息。例如，我们可能希望将日志消息写入磁盘的脚本仅接收严重错误，而不是在警告或信息日志消息上浪费磁盘空间。 

我们使用的是扇出交换，它没有给我们太大的灵活性 - 它只能进行无意识的广播。 

我们将使用`直接交换`。直接交换背后的路由算法很简单 - 消息进入队列，其绑定密钥与消息的路由密钥`完全匹配`。 
![直接交换](http://pic.pwwtest.com/%E7%9B%B4%E6%8E%A5%E4%BA%A4%E6%8D%A2-2018-08-16_220627.png) 

在此设置中，我们可以看到直接交换X与两个绑定到它的队列。第一个队列绑定橙色绑定，第二个绑定有两个绑定，一个绑定密钥为黑色，另一个绑定为绿色。

在这样的设置中，使用路由密钥orange发布到交换机的消息 将被路由到队列Q1。路由键为黑色 或绿色的消息将转到Q2。所有其他消息将被丢弃。 

### 多个绑定
![多个绑定](http://pic.pwwtest.com/%E5%A4%9A%E4%B8%AA%E7%BB%91%E5%AE%9A-2018-08-16_220642.png) 

使用相同的绑定密钥绑定多个队列是完全合法的。在我们的例子中，我们可以在X和Q1之间添加绑定键黑色的绑定。在这种情况下，直接交换将表现得像扇出一样，并将消息广播到所有匹配的队列。路由密钥为黑色的消息将传送到  Q1和Q2。 

### 发送日志
我们将此模型用于我们的日志系统。我们会将消息发送给直接交换，而不是扇出。我们将提供日志严重性作为路由密钥。这样接收脚本将能够选择它想要接收的严重性。让我们首先关注发送日志。 

一如既往，我们需要先创建一个交换： 
```php
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
```

我们已准备好发送消息： 
```php
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
$channel->basic_publish($msg, 'direct_logs', $severity);
```

### 订阅
接收消息将像上一个教程一样工作，但有一个例外 - 我们将为我们感兴趣的每个严重性创建一个新的绑定。 
```php
foreach ($severities as $severity) {
    $channel->queue_bind($queue_name, 'direct_logs', $severity);
}
```

### 完整代码
![直接交换多个绑定](http://pic.pwwtest.com/%E7%9B%B4%E6%8E%A5%E4%BA%A4%E6%8D%A2%E5%A4%9A%E4%B8%AA%E7%BB%91%E5%AE%9A2018-09-02_231850.png) 

emit_log_direct.php类的代码： 
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
$severity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';
$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, 'direct_logs', $severity);
echo ' [x] Sent ', $severity, ':', $data, "\n";
$channel->close();
$connection->close();
```

receive_logs_direct.php的代码： 
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare('direct_logs', 'direct', false, false, false);
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$severities = array_slice($argv, 1);
if (empty($severities)) {
    echo '缺少安全级别参数', "\n";
    exit(1);
}
foreach ($severities as $severity) {
    $channel->queue_bind($queue_name, 'direct_logs', $severity);
}
echo " [*] Waiting for logs. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
};
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
```

测试结果如图： 
![直接绑定测试结果](http://pic.pwwtest.com/%E7%9B%B4%E6%8E%A5%E4%BA%A4%E6%8D%A2%E6%B5%8B%E8%AF%95-2018-08-16_224540.png) 

## Topics主题
虽然使用直接交换改进了我们的系统，但它仍然有局限性 - 它不能基于多个标准进行路由。 

我们需要了解更复杂的`主题交换`。 

### 主题交换
发送到主题交换的消息不能具有任意 routing_key - 它必须是`由点分隔的单词列表`。单词可以是任何内容，但通常它们指定与消息相关的一些功能。一些有效的路由密钥示例："stock.usd.nyse", "nyse.vmw", "quick.orange.rabbit"。路由密钥中可以包含任意数量的单词，最多可达255个字节。 

`绑定密钥也必须采用相同的形式`。主题交换背后的逻辑 类似于直接交换- 使用特定路由密钥发送的消息将被传递到与匹配绑定密钥绑定的所有队列。但是，绑定键有两个重要的特殊情况：  
- *（星号）可以替代一个单词。 
- #（hash）可以替换零个或多个单词。 

在一个例子中解释这个是最容易的： 
![主题交换](http://pic.pwwtest.com/%E4%B8%BB%E9%A2%98%E4%BA%A4%E6%8D%A2_20180819164715.png) 

我们创建了三个绑定：Q1绑定了绑定键"* .orange.*", Q2 绑定了"*.*.rabbit"和"lazy.#"。 

这些绑定可以概括为： 
- Q1对所有橙色动物感兴趣。
- Q2希望听到关于兔子的一切，以及关于懒惰动物的一切。 

路由密钥设置为"quick.orange.rabbit"的消息将传递到两个队列。 
消息"lazy.orange.elephant"也将同时发送给他们。 
另一方面，"quick.orange.fox"只会进入第一个队列，而"lazy.brown.fox"只会进入第二个队列。 
"lazy.pink.rabbit"将仅传递到第二个队列一次，即使它匹配两个绑定。
"quick.brown.fox"与任何绑定都不匹配，因此它将被丢弃。 

如果我们违反规则并发送带有一个或四个单词的消息，例如"orange"或"quick.orange.male.rabbit"，会发生什么？好吧，这些消息将不匹配任何绑定，将丢失。 

另一方面，"lazy.orange.male.rabbit"，即使它有四个单词，也会匹配最后一个绑定，并将被传递到第二个队列。 

> **主题交换** 
> 主题交换功能强大，可以像其他交换器一样。 
>
> 当队列与"#"（哈希）绑定密钥绑定时 - 它将接收所有消息，而不管路由密钥 - 如扇出交换。 
>
> 当特殊字符"*"（星号）和"#"（哈希）未在绑定中使用时，主题交换的行为就像直接交换一样。 

### 完整代码
emit_log_topic.php的代码： 
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare('topic_logs', 'topic', false, false, false);
$routing_key = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'anonymous.info';
$data = implode(' ', array_slice($argv, 2));
if (empty($data)) {
    $data = "Hello World!";
}
$msg = new AMQPMessage($data);
$channel->basic_publish($msg, 'topic_logs', $routing_key);
echo ' [x] Sent ', $routing_key, ':', $data, "\n";
$channel->close();
$connection->close();
```

receive_logs_topic.php的代码： 
```php
<?php
require_once __DIR__ . '/vendor/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection('localhost', 5672, 'guest', 'guest');
$channel = $connection->channel();
$channel->exchange_declare('topic_logs', 'topic', false, false, false);
list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);
$binding_keys = array_slice($argv, 1);
if (empty($binding_keys)) {
    echo '缺少安全级别参数', "\n";
    exit(1);
}
foreach ($binding_keys as $binding_key) {
    $channel->queue_bind($queue_name, 'topic_logs', $binding_key);
}
echo " [*] Waiting for logs. To exit press CTRL+C\n";
$callback = function ($msg) {
    echo ' [x] ', $msg->delivery_info['routing_key'], ':', $msg->body, "\n";
};
$channel->basic_consume($queue_name, '', false, true, false, false, $callback);
while (count($channel->callbacks)) {
    $channel->wait();
}
$channel->close();
$connection->close();
```

测试结果如图： 
![主题交换测试](http://pic.pwwtest.com/%E4%B8%BB%E9%A2%98%E4%BA%A4%E6%8D%A2%E6%B5%8B%E8%AF%952018-09-02_235515.png)

## rpc远程过程调用
目前接触不多，而且问题会比较多，暂不赘述 

了解链接：http://www.rabbitmq.com/tutorials/tutorial-six-php.html 


