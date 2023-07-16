<?php

    require './ScrapingProperties.php';
    //Arquivo de configuração para aumentar o limite de memória e tempo do php
    require './config.php';
    class RealEstateLinkScraping{
        private $domDocument;
        private $domXPath;
        private String $contentPage;
        private array $data = [];
        private $index = 1;
        private $limit = 0;
        public function __construct(private String $linkStart){
            $this->grabContentPageAndSaveContent($this->linkStart);
            $this->initializeDomDocumentClassAndLoadHtml();
            $this->initializeDomXpathClass();

            $this->getAllLinkOfRealState();
        }

        private function initializeDomDocumentClassAndLoadHtml(){
            $this->domDocument = new DOMDocument();
            $this->loadHTML();
        }

        private function initializeDomXpathClass(){
            $this->domXPath = new DOMXPath($this->domDocument);
        }
        private function grabContentPageAndSaveContent(String $propertyLinkToScraping){
            try{
                $this->contentPage = file_get_contents($propertyLinkToScraping);
            }catch(Error $getContentError){
                print_r($getContentError);
            }
        }
        private function loadHTML(){
            $this->domDocument->loadHTML($this->contentPage);
        }
        //Função recursiva para obter todos os dados
        public function scrapping(){
            $links = $this->getAllLinkOfRealState();

            foreach($links as $link){
                $ScrapingProperties = new ScrapingProperties($link);
                array_push($this->data, $ScrapingProperties->getDataToArray());
            }

            /*
             * É recomendado definir um limite de páginas, pois o tempo para terminar pode ser longo e pode chegar a horas.
            */
            if(!empty($this->getNextPageLink()) && $this->index <= $this->limit || !empty($this->getNextPageLink()) && $this->limit == 0){
                $this->nextPage($this->getNextPageLink());
                $this->index++;
                $this->scrapping();
            }
            
        }
        public function setLimit(int $limit){
            $this->limit = $limit;
        }

        //Captura todos os links de uma página
        public function getAllLinkOfRealState(){

            $queryCommand = "//div[@class='my-auto bd-highlight text-center']//a/@href";
            $resultFromSearch = $this->domXPath->query($queryCommand);
      
            $allLinks = [];
            foreach($resultFromSearch as $links){
                array_push($allLinks, $links->nodeValue);
            }
            
            return $allLinks;
        }
        //Função criada para mostrar os dados capturado, mas o melhor seria cadastrar num banco de dados
        public function showData(){
            print_r($this->data);
        }

        public function getNextPageLink(): string{
            $classNameToSearch = 'page-link';
            $queryCommand = "//a[contains(@class, '{$classNameToSearch}')]";
            return $this->domXPath->query($queryCommand)->item(12)->getAttribute('href');
        }

        private function nextPage($link): void{
            $this->grabContentPageAndSaveContent($link);
            $this->initializeDomDocumentClassAndLoadHtml();
            $this->initializeDomXpathClass();
        }

    }
    $scrapping = new RealEstateLinkScraping('https://agostinholeiloes.com.br/lotes/imovel?tipo=imovel&categoria_id=1&data_leilao_ini=&data_leilao_fim=&lance_inicial_ini=&lance_inicial_fim=&address_uf=&address_cidade_ibge=&address_logradouro=&comitente_id=&search=&page=1');
    //Definindo o limite de páginas para páginar.
    $scrapping->setLimit(3);
    $scrapping->scrapping();
    $scrapping->showData();
    