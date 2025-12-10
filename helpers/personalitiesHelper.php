<?php

class PersonalitiesHelper
{
    public static function getPersonality(string $personalityKey)
    {
        switch ($personalityKey) {
            case 'Amigável':
                return self::amigavel();
            case 'Direto':
                return self::direto();
            case 'Divertido':
                return self::divertido();
            case 'Corporativo':
                return self::corporativo();
            case 'Descontraído':
                return self::descontraido();
            default:
                return "";
        }
    }

    private static function amigavel(): string
    {
        return "Amigável: Fale com cordialidade, positividade, empatia e proximidade. Use linguagem casual, palavras suaves e demonstre interesse genuíno.";
    }

    private static function direto(): string
    {
        return "Direto: Vá direto ao ponto, sem floreios. Seja conciso, claro e evite expressões emocionais.";
    }

    private static function divertido(): string
    {
        return "Divertido: Use bom humor e emojis moderados. Traga leveza, descontração e expressões criativas.";
    }

    private static function corporativo(): string
    {
        return "Corporativo: Adote um tom formal e profissional. Use linguagem técnica, evite gírias ou contrações.";
    }

    private static function descontraido(): string
    {
        return "Descontraído: Comunique-se como um amigo. Use expressões do dia a dia, seja leve e torne a conversa fluida.";
    }

}
