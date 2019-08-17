<?php
use Symfony\Component\HttpClient\HttpClient;

require 'vendor/autoload.php';

if(file_exists('.env')) {
    $dotenv = Dotenv\Dotenv::create(__DIR__);
    $dotenv->overload();
}

$client = HttpClient::create([
    'headers' => [
        'accept' => 'application/vnd.github.v3+json',
        'authorization' => 'token '.getenv('TOKEN')
    ]
]);

$baseUrl = 'https://api.github.com';
if (!in_array($argv[1], ['lock', 'unlock', 'etapa1', 'etapa2'])) {
    echo "passe apenas os argumentos lock ou unlock\n";
    return;
}

switch ($argv[1]) {
    case 'lock':
        $response = $client->request('GET', $baseUrl.'/user/repos?per_page=100&affiliation=owner');
        $content = json_decode($response->getContent());
        foreach ($content as $repo) {
            if ($repo->fork) {
                continue;
            }
            preg_match('/'.getenv('PREFIX').'(?<nome>.*)/', $repo->name, $matches);
            if (!$matches) {
                continue;
            }
            $response = $client->request('GET', $baseUrl.'/repos/'.$repo->full_name.'/collaborators');
            $collaborators = json_decode($response->getContent());
            $data = [
                'name' => $repo->name,
                'html_url' => $repo->html_url
            ];
            foreach ($collaborators as $collaborator) {
                if ($collaborator->login == getenv('OWNER')) {
                    continue;
                }
                $data['collaborators'][] = $collaborator->login;
                $client->request('DELETE', $baseUrl.'/repos/'.getenv('OWNER').'/'.$repo->name.'/collaborators/'.$collaborator->login);
            }
            if (isset($data['collaborators'])) {
                $repos[] = $data;
            }
        }
        file_put_contents('dados.json', json_encode($repos));
        break;
    case 'unlock':
        $repos = json_decode(file_get_contents('dados.json'));
        foreach ($repos as $repo) {
            foreach ($repo->collaborators as $login) {
                $client->request('PUT', $baseUrl.'/repos/'.getenv('OWNER').'/'.$repo->name.'/collaborators/'.$login);
            }
        }
        break;
    case 'etapa1':
        $repos = json_decode(file_get_contents('dados.json'));
        foreach ($repos as $repo) {
            if ($repo->fork) {
                continue;
            }
            preg_match('/'.getenv('PREFIX').'(?<nome>.*)/', $repo->name, $matches);
            if (!$matches) {
                continue;
            }
            preg_match('/'.getenv('PREFIX').'(?<name>.*)/', $repo->name, $matches);
            $client->request('PATCH', $baseUrl.'/repos/'.getenv('OWNER').'/'.$repo->name, [
                'body' => json_encode(['private' => false])
            ]);
            $command = 'svn export '.$repo->html_url.$argv[2];
            shell_exec($command);
            shell_exec('mv resumo-etapa1 '.$matches['name']);
            $client->request('PATCH', $baseUrl.'/repos/'.getenv('OWNER').'/'.$repo->name, [
                'body' => json_encode(['private' => true])
            ]);
        }
        foreach ($repos as $repo) {
            preg_match('/'.getenv('PREFIX').'(?<name>.*)/', $repo->name, $matches);
            shell_exec('tar -czf '.$matches['name']. '.tar.gz '.$matches['name']);
            shell_exec('rm -rf '.$matches['name']);
        }
        break;
}
