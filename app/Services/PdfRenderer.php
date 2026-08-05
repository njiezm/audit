<?php

namespace App\Services;

use App\Models\Audit;
use App\Models\Specification;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as PdfWrapper;

class PdfRenderer
{
    /** Rapport d'audit, cahier des charges accolé le cas échéant. */
    public function make(Audit $audit): PdfWrapper
    {
        $audit->loadMissing([
            'categories.attachments',
            'client',
            'signatory',
            'specification.sections',
            'specification.lots',
        ]);

        // La variable est passée explicitement à la vue : l'ancien
        // view()->share() la publiait dans *toutes* les vues de la requête.
        return $this->configure(Pdf::loadView('audits.pdf', ['audit' => $audit]));
    }

    /** Cahier des charges livré seul, sans le rapport d'audit. */
    public function makeSpecification(Specification $specification): PdfWrapper
    {
        $specification->loadMissing(['sections', 'lots', 'audit.client']);

        return $this->configure(Pdf::loadView('specifications.pdf', [
            'audit' => $specification->audit,
            'specification' => $specification,
        ]));
    }

    private function configure(PdfWrapper $pdf): PdfWrapper
    {
        return $pdf
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
