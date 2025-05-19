<?php
/**
 * Classe responsável por ler o conteúdo de sites via URL.
 *
 * Esta classe utiliza a API HTTP do WordPress para realizar requisições
 * e o DOMDocument para extrair o conteúdo textual dos elementos HTML.
 *
 * Exemplo de uso:
 *     $conteudo = SiteReader::read_content('https://exemplo.com');
 *     echo $conteudo;
 */

class SiteReader {

    /**
     * Lê e extrai o conteúdo textual de um site.
     *
     * @param string $url URL do site a ser lido.
     * @return string Conteúdo extraído ou mensagem de erro.
     */
    public static function read_content( $url ) {
        // Valida a URL
        if ( ! filter_var( $url, FILTER_VALIDATE_URL ) ) {
            return "URL inválida.";
        }

        // Realiza a requisição HTTP utilizando a API do WordPress
        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            return "Erro ao acessar a URL: " . $response->get_error_message();
        }

        // Recupera o corpo da resposta (HTML)
        $html = wp_remote_retrieve_body( $response );
        if ( empty( $html ) ) {
            return "Nenhum conteúdo encontrado na URL.";
        }

        // Cria um objeto DOMDocument para processar o HTML
        $dom = new DOMDocument;
        // Suprime warnings de HTML mal formatado
        @$dom->loadHTML( $html );

        $content = "";
        // Extrai e concatena o texto de todos os parágrafos (<p>)
        foreach ( $dom->getElementsByTagName( 'p' ) as $p ) {
            $content .= trim( $p->nodeValue ) . "\n";
        }

        return $content;
    }
}
