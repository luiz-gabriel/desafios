<?php

    class ScrapingProperties{
        private $domDocument;
        private $domXPath;
        private String $contentPage;
        private String $title;
        private float $assensmentValue;
        private DateTime $dateOfFirstSquare;
        private float $secondSquareValue;
        private String $address;
        private String $city;
        private String $state;
        private array $documentLinks = [];
        private array $imagesLinks = [];

        public function __construct(private String $propertyLinkToScraping){
            $this->grabContentPageAndSaveContent();
            $this->initializeDomDocumentClassAndLoadHtml();
            $this->initializeDomXpathClass();
            /**
             * Setando valores nas variaveis 
             */

            $this->title = $this->grabTitle();
            $this->assensmentValue = $this->grabAssensmentValue(); 
            $this->dateOfFirstSquare = new DateTime($this->grabDateOfFirstSquare());
            $this->secondSquareValue = $this->grabSecondSquareValue();
            $this->address = $this->grabAddress();
            $this->city = $this->grabCity();
            $this->state = $this->grabState();
            $this->documentLinks = $this->grabDocumentLinks();
            $this->imagesLinks = $this->grabImagesLinks();
        }

        private function initializeDomDocumentClassAndLoadHtml(){
            $this->domDocument = new DOMDocument();
            $this->loadHTML();
        }

        private function initializeDomXpathClass(){
            $this->domXPath = new DOMXPath($this->domDocument);
        }
        private function grabContentPageAndSaveContent(){
            $this->contentPage = file_get_contents($this->propertyLinkToScraping);
        }
        private function loadHTML(){
            $this->domDocument->loadHTML($this->contentPage);
        }
        //Getters para obter os valores 
        public function getTitle(): string {
            return $this->title;
        }
    
        public function getAssensmentValue(): float {
            return $this->assensmentValue;
        }
    
        public function getDateOfFirstSquare(): DateTime {
            return $this->dateOfFirstSquare;
        }
    
        public function getSecondSquareValue(): float {
            return $this->secondSquareValue;
        }
    
        public function getAddress(): string {
            return $this->address;
        }
    
        public function getCity(): string {
            return $this->city;
        }
    
        public function getState(): string {
            return $this->state;
        }
    
        public function getDocumentLinks(): array {
            return $this->documentLinks;
        }
    
        public function getImagesLinks(): array {
            return $this->imagesLinks;
        }

        //Retorno de dados em array

        public function getDataToArray(){
            return [
                'title' => $this->getTitle(),
                'assessmentValue' => $this->getAssensmentValue(),
                'dateOfFirstSquare' => $this->getDateOfFirstSquare()->format('Y-m-d'),
                'secondSquareValue' => $this->getSecondSquareValue(),
                'address' => $this->getAddress(),
                'city' => $this->getCity(),
                'state' => $this->getState(),
                'documentLinks' => implode(', ', $this->getDocumentLinks()),
                'imagesLinks' => implode(', ', $this->getImagesLinks())
            ];
        }

        //Funções para pegar o conteudo da página
        public function grabTitle(bool $unifyTitleAndBatch = false): String {
            $classNameToSearch = 'px-1';
            $queryCommand = "//div[contains(@class, '{$classNameToSearch}')]/child::*";
            $queryResultForgrabTitle = $this->domXPath->query($queryCommand);
            return $unifyTitleAndBatch == false ? $queryResultForgrabTitle->item(1)->nodeValue : $queryResultForgrabTitle->item(0)->nodeValue . ' '. $queryResultForgrabTitle->item(1)->nodeValue;
        }

        public function grabAssensmentValue() : float{
            $valueToSearch = "Valor de Avaliação";
            $queryCommand = "//strong[contains(text(), '{$valueToSearch}')]/parent::node()";
            $surveyResultOnAppraisedValue = $this->domXPath->query($queryCommand)->item(0)->nodeValue;

            $assentmentValueFormated = str_replace(['R$', '.',' ',','], '', explode(':', $surveyResultOnAppraisedValue)[1]);
            return (float) $assentmentValueFormated;
        }

        public function grabDateOfFirstSquare():string{
            $valueToSearch = "Data 1º Leilão";
            $queryCommand = "//strong[contains(text(), '{$valueToSearch}')]/parent::node()";
            $dateCaptured = str_replace('Data 1º Leilão: ','',$this->domXPath->query($queryCommand)->item(0)->nodeValue);
            return str_replace('Data 1º Leilão: ','',$this->domXPath->query($queryCommand)->item(0)->nodeValue);
        }

        public function grabSecondSquareValue(): float{
            $valueToSearch = "Lance Inicial";
            $queryCommand = "//strong[contains(text(), '{$valueToSearch}')]/parent::node()";

            $resultOnSecondSquareValue = $this->domXPath->query($queryCommand)->item(1)->nodeValue;
            $squareValueNotFormated = explode(':', $resultOnSecondSquareValue)[1];
            return !empty($squareValueNotFormated) ? (float) str_replace(['R$', '.',' ',','], '', $squareValueNotFormated) : 0;
        }

        public function grabAddress() : String{
            $valueToSearch = "Endereço";
            $queryCommand = "//b[contains(text(), '{$valueToSearch}')]/following-sibling::text()";
            return trim($this->domXPath->query($queryCommand)->item(0)->nodeValue);
        }

        public function grabCity(){
            $valueToSearch = "Cidade";
            $queryCommand = "//b[contains(text(), '{$valueToSearch}')]/following-sibling::text()";
            return trim(explode('/',$this->domXPath->query($queryCommand)->item(0)->nodeValue)[0]);
        }

        public function grabState(){
            $valueToSearch = "Cidade";
            $queryCommand = "//b[contains(text(), '{$valueToSearch}')]/following-sibling::text()";
            return trim(explode('/',$this->domXPath->query($queryCommand)->item(0)->nodeValue)[1]);
        }
        
        public function grabDocumentLinks(): array{
            $classNameToSearch = 'btn-outline-secondary';
            $queryCommand = "//a[contains(@class, '{$classNameToSearch}')]";
            
            $foundDocumentLinkResults = $this->domXPath->query($queryCommand);
            $documentsLink = [];
            
            $i = 0;
            while($i < count($foundDocumentLinkResults)){
                if(!is_null($foundDocumentLinkResults->item($i)->getAttribute('href')) && strpos($foundDocumentLinkResults->item($i)->getAttribute('href'), '.pdf') !== false){
                    array_push($documentsLink, $foundDocumentLinkResults->item($i)->getAttribute('href'));
                }
                $i++;
            }

            return $documentsLink;
        }

        public function grabImagesLinks(): array{
            
            $classNameToSearch = 'carousel-item';
            $queryCommand = "//div[contains(@class, '{$classNameToSearch}')]/a";
            $imagesCaptureResult = $this->domXPath->query($queryCommand);

            $imagesLinks = [];

            if(count($imagesCaptureResult) > 0){
                foreach($imagesCaptureResult as $imgLink){
                    array_push($imagesLinks, $imgLink->getAttribute('href'));
                }
            }else{
                $imageCaptureResult = $this->domXPath->query("//div[contains(@style, 'background: url')]")->item(0)->getAttribute('style');

                array_push($imagesLinks, str_replace(["url('","')"],'',explode(" ", $imageCaptureResult)[4]));
            }
            
            return $imagesLinks;
        }


    }

