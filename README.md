WP Monitor API  
===  
  
Este plugin visa expandir a API padrão do WordPress permitindo um maior monitoramento e acompanhamento do site, trazendo informações relacionadas ao ambiente (como versões PHP, Apache, Sistema Operacional, etc), temas e plugins instalados, usuários, etc.   
  
Começando  
---------------  
  
Para utilizar o plugin é muito simples, após devidamente instalado e ativo, vá até o menu **Configurações**, submenu **WP Monitor API**. Basta adicionar sua chave de segurança e pronto! Sua API foi expandida está pronta para uso!

### Consumindo a API
Para recuperar os dados da sua API, é preciso enviar no cabeçalho da sua requisição parâmetros para garantir o acesso aos dados (por questões óbvias de segurança!), para tanto, basta incluir na requisição do tipo *Basic Auth* (Autenticação Básica), os dados de usuário e senha.

Para a autenticação, o usuário padrão será o slug do plugin: **wp-monitor-api**, e a senha será a definida através das configurações do seu site (conforme etapa anterior).

### Mais informações

 - [Sobre a REST API do WordPress](https://developer.wordpress.org/rest-api/);
 - [Autenticação HTTP com PHP](http://php.net/manual/pt_BR/features.http-auth.php);
 - [Dashboard para consumo da API](https://github.com/Darciro/WP-Monitor-Dashboard).

> Autor: [Ricardo Carvalho](https://github.com/Darciro).