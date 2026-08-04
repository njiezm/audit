<?php

namespace App\Services;

use App\Models\Audit;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;

class PdfRenderer
{
    public function make(Audit $audit): PdfWrapper
    {
        $audit->loadMissing(['categories.attachments', 'client', 'signatory']);

        // La variable est passée explicitement à la vue : l'ancien
        // view()->share() la publiait dans *toutes* les vues de la requête.
        return Pdf::loadView('audits.pdf', ['audit' => $audit])
            ->setPaper('a4', 'portrait')
            // setOption() modifie l'objet Options existant. setOptions() le
            // *remplace* : on y perdrait `font_dir`, et DomPDF irait chercher
            // les polices compilées dans son propre dossier vendor, où elles
            // ne sont pas — l'erreur « Undefined array key .../lib/fonts/… ».
            ->setOption('isRemoteEnabled', false)   // aucune ressource distante
            ->setOption('isPhpEnabled', false)
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('defaultMediaType', 'print');
    }
}
