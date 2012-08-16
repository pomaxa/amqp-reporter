<?php
/**
 * Created by JetBrains PhpStorm.
 * @author: pomaxa none <pomaxa@gmail.com>
 * @date: 8/16/12
 *
 */
class amqpReporter
{
    /**
     * exchange name
     */
    const EXCHANGE_NAME = 'log';

    /**
     * vhost name
     */
    const VHOST_NAME = 'log';

    /**
     * represents connection with amqp broker
     * @var AMQPConnection
     */
    protected $connection;

    /**
     * represents exchange
     * @var AMQPExchange
     */
    protected $exchange;

    /**
     * creates instance, constructs objects, connects to amqp
     * $params should countain data as follows:
     * - host
     * - login
     * - password
     * - port
     * @param array $params
     * @throws Exception
     */
    public function __construct(array $params = array())
    {
        $params = array_merge(array('host' => 'localhost',
                'login' => '',
                'password' => '',
                'port' => 5672),
            $params);

        //check for class amqp existence! use confluence for installation instructions
        if (!class_exists('AMQPConnection') || !class_exists('AMQPExchange')) {
            throw new \Exception(' AMQP class does not exist! Please install it! ');
        }

        //set up connection
        $this->connection = new AMQPConnection();
        $this->connection->setHost($params['host']);
        $this->connection->setLogin($params['login']);
        $this->connection->setPassword($params['password']);
        $this->connection->setPort($params['port']);
        $this->connection->setVhost(self::VHOST_NAME);

        $this->connection->connect();

        if (!class_exists('AMQPChannel')) {
            //i.e AMQP version is < 1
            $this->exchange = new AMQPExchange($this->connection, self::EXCHANGE_NAME);
        } else {
            //i.e AMQP version is > 1, and we need to use new construction
            $channel = new \AMQPChannel($this->connection);
            $this->exchange = new AMQPExchange($channel);
            $this->exchange->setName(self::EXCHANGE_NAME);
        }
    }

    /**
     * destructor
     */
    public function __destruct()
    {
        $this->connection->disconnect();
    }

    /**
     * reports to the AMQP server
     * $queueName should be like "auth", and
     * $data like array('game_id' => 12);
     * @param string $queueName
     * @param array $data
     */
    public function report($queueName, array $data)
    {
        $data = json_encode($data);

        return $this->exchange->publish($data, $queueName, 0,
            array('Content-type' => 'application/json',
                'delivery_mode' => 2));
    }

}
