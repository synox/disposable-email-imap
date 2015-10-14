<?php
require_once 'vendor/autoload.php';

// --- CONFIG
$mailbox = new PhpImap\Mailbox('{10.0.5.3/imap/ssl/novalidate-cert}INBOX', 'test', 'test');
// change this, example: $mailbox = new PhpImap\Mailbox('{mail.server.com:993/imap/ssl}INBOX', 'user123', 'myp4ssw0rd');

date_default_timezone_set('Europe/Paris');

# enable in development mode:
#error_reporting(E_ALL);

# get hostname from request if possible - or define your own
$serverName = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
define('DOMAIN', str_replace('www.', '', $serverName));

// URI-Redirector Prefix (leave empty for direct links)
define('URI_REDIRECT_PREFIX', "http://www.redirect.am/?"); 
// --- end of CONFIG

// setup slim & twig
$app = new \Slim\Slim(array(
    'view' => new \Slim\Views\Twig(),
    'templates.path' => 'app/templates',
    'slim.url_scheme' => 'https',
));

$app->error(function (\Exception $e) use ($app) {
    error_log($e->getMessage());
    $app->callErrorHandler($e);
});

$app->view()->parserOptions = array(
    'cache' => __DIR__ . '/cache'
);
$app->view()->parserExtensions = array(
    new \Slim\Views\TwigExtension(),
    new DisposableEmail\AutoLinkTwigExtension()
);

// add html2Text filter
$app->view()->getInstance()->addFilter(new Twig_SimpleFilter('html2Text', function ($string) {
    $convert = new \Html2Text\Html2Text($string);
    return $convert->get_text();
}));

// redirect to random address
$app->get('/', function () use ($app) {
    $wordLength = rand(3, 8);
    $container = new PronounceableWord_DependencyInjectionContainer();
    $generator = $container->getGenerator();
    $word = $generator->generateWordOfGivenLength($wordLength);
    $nr = rand(51, 91);
    $name = $word . $nr ;

    $app->redirect($app->urlFor('read', array('name' => $name)));
})->name('home');

// switch to other account using form
$app->post('/switch', function () use ($app) {
    $name = cleanName($app->request->params('name'));
    if(strlen($name)== 0) {
      $app->redirect($app->urlFor('home'));
    }
    $app->redirect($app->urlFor('read', array('name' => $name)));
})->name('switch');

// ajax check to see if there is new mail
$app->get('/check/:name', function ($name) use ($app,$mailbox) {
    $name = cleanName($name);
    $mailsIds = findMails($name, $mailbox);
    print sizeOf($mailsIds);
})->name('mailcount');

// delete an email
$app->get('/:name/delete/:id', function ($name, $id) use ($app, $mailbox) {
    $mailbox->deleteMail($id);
    $mailbox->expungeDeletedMails();
    $app->redirect($app->urlFor('read', array('name' => $name)));
})->name('delete');

// read raw source
$app->get('/:name/source/:id', function ($name, $id) use ($app,$mailbox) {
    $email = imap_fetchbody($mailbox->getImapStream(), $id, "", FT_UID);
    if(! is_null($email)) {
        $app->contentType("text/plain");
        print $email;
    } else {
      $app->notFound();
    }
})->name('source');

// show html
$app->get('/:name/html/:id', function ($name, $id) use ($app, $mailbox) {
    $email = $mailbox->getMail($id);
    if(! is_null($email)) {
        $html_safe = preg_replace("/https?:\\/\\//i", URI_REDIRECT_PREFIX . '\\0', $email->textHtml);
        print $html_safe;
    } else {
      $app->notFound();
    }
})->name('html');

// read emails
$app->get('/:name/', function ($name) use ($app,$mailbox) {
    $name = cleanName($name);    
    $mailsIds = findMails($name, $mailbox);
    
    $emails = array();
    foreach ($mailsIds as $id) {
      $emails[] = $mailbox->getMail($id);
    }
    
    $address = $name . '@' .DOMAIN;
    if ( $emails === NULL || count($emails) == 0) {
        $app->render('waiting.html',array('name' => $name, 'address' => $address));
    } else {
        $app->render('list.html',array('name' => $name, 'address' => $address, 'emails'=>$emails));
    }
})->name('read');

$app->run();


function cleanName($name) {
  $name = preg_replace('/@.*$/', "", $name);   
  $name =  preg_replace('/[^A-Za-z0-9_.+-]/', "", $name);   // makes it safe
  if(strlen($name) === 0) {
    return "_";
  }
  return $name;
}

function findMails($name, $mailbox) {
  if(strlen($name) === 0 || $name == "_") {
    return array();
  }
  $mailsIds = imap_sort($mailbox->getImapStream(), SORTARRIVAL, true, SE_UID, 'TO "'.$name.'@"');
  return $mailsIds;
}


// cleanup old messages
$before = date('d-M-Y', strtotime('30 days ago'));
$mailsIds = $mailbox->searchMailBox('BEFORE '.$before); 
foreach ($mailsIds as $id) {
  $mailbox->deleteMail($id);
}
$mailbox->expungeDeletedMails();
