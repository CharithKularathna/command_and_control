<?php
class MessageController extends Controller
{
    public function __construct($controller,$action)
    {
        parent::__construct($controller,$action);
        $this->load_model('messages');
    }

    public function indexAction()
{

}

public function sendAction($reciever,$sender,$content)
{
    $this->MessagesModel->insert($reciever,$sender,$content);
}
}


?>