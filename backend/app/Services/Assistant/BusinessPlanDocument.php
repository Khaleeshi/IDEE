<?php

namespace App\Services\Assistant;

use App\Enums\AssistantStatus;
use App\Models\BusinessPlan;
use App\Models\Project;
use RuntimeException;
use ZipArchive;

/**
 * Export du business plan au format Word (section 7) : un fichier .docx simple,
 * que le porteur retouche dans Word, LibreOffice ou Google Docs.
 */
class BusinessPlanDocument
{
    public const MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    /**
     * Dernière version prête du business plan d'un projet.
     */
    public function latestReady(Project $project): BusinessPlan
    {
        return BusinessPlan::where('project_id', $project->id)
            ->where('status', AssistantStatus::Pret)
            ->orderByDesc('version')
            ->firstOrFail();
    }

    public function fileName(Project $project): string
    {
        return 'business-plan-'.(str($project->title)->slug()->limit(60, '')->value() ?: 'projet').'.docx';
    }

    /**
     * Contenu du fichier .docx.
     */
    public function docx(Project $project, BusinessPlan $plan): string
    {
        $path = tempnam(sys_get_temp_dir(), 'idee-docx-');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Impossible de préparer le fichier Word.');
        }

        $zip->addFromString('[Content_Types].xml', $this->contentTypes());
        $zip->addFromString('_rels/.rels', $this->packageRelationships());
        $zip->addFromString('docProps/core.xml', $this->coreProperties($project));
        $zip->addFromString('word/_rels/document.xml.rels', $this->documentRelationships());
        $zip->addFromString('word/styles.xml', $this->styles());
        $zip->addFromString('word/document.xml', $this->document($project, $plan));
        $zip->close();

        $content = (string) file_get_contents($path);
        unlink($path);

        return $content;
    }

    private function document(Project $project, BusinessPlan $plan): string
    {
        $date = ($plan->edited_at ?? $plan->generated_at)?->translatedFormat('d F Y');
        $body = $this->paragraph($project->title, 'Title')
            .$this->paragraph('Business plan · version '.$plan->version.($date ? ' · '.$date : ''), 'Subtitle');

        foreach ($plan->sections ?? [] as $section) {
            if (blank($section['content'] ?? null)) {
                continue;
            }

            $body .= $this->paragraph($section['title'] ?? '', 'Heading1');

            foreach (preg_split('/\R/u', (string) $section['content']) as $line) {
                $body .= $this->paragraph($line);
            }
        }

        if ($plan->assumptions) {
            $body .= $this->paragraph('Hypothèses', 'Heading1')
                .$this->table([
                    ['Hypothèse', 'Valeur', 'Sur quoi elle repose'],
                    ...array_map(fn (array $assumption) => [
                        (string) ($assumption['label'] ?? ''),
                        (string) ($assumption['value'] ?? ''),
                        (string) ($assumption['basis'] ?? ''),
                    ], $plan->assumptions),
                ]);
        }

        $body .= $this->paragraph('Généré avec l\'IA, à vérifier. Document préparé par le porteur du projet avec l\'assistant d\'IDEE. Les chiffres sont des hypothèses à vérifier ; ce document n\'est ni une offre de titres ni une promesse de rendement.', 'Note');

        return $this->xml('<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'
            .$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1418" w:right="1418" w:bottom="1418" w:left="1418" w:header="709" w:footer="709" w:gutter="0"/></w:sectPr>'
            .'</w:body></w:document>');
    }

    private function paragraph(string $text, ?string $style = null): string
    {
        $properties = $style ? '<w:pPr><w:pStyle w:val="'.$style.'"/></w:pPr>' : '';

        return '<w:p>'.$properties.'<w:r><w:t xml:space="preserve">'.$this->escape($text).'</w:t></w:r></w:p>';
    }

    /**
     * @param  list<list<string>>  $rows  la première ligne sert d'en-tête
     */
    private function table(array $rows): string
    {
        $border = 'w:val="single" w:sz="4" w:space="0" w:color="BFBFBF"';
        $xml = '<w:tbl><w:tblPr><w:tblW w:w="5000" w:type="pct"/><w:tblBorders>'
            ."<w:top {$border}/><w:left {$border}/><w:bottom {$border}/><w:right {$border}/><w:insideH {$border}/><w:insideV {$border}/>"
            .'</w:tblBorders></w:tblPr><w:tblGrid><w:gridCol w:w="3000"/><w:gridCol w:w="2000"/><w:gridCol w:w="4000"/></w:tblGrid>';

        foreach ($rows as $index => $cells) {
            $xml .= '<w:tr>';
            foreach ($cells as $cell) {
                $run = $index === 0 ? '<w:rPr><w:b/></w:rPr>' : '';
                $xml .= '<w:tc><w:p><w:r>'.$run.'<w:t xml:space="preserve">'.$this->escape($cell).'</w:t></w:r></w:p></w:tc>';
            }
            $xml .= '</w:tr>';
        }

        return $xml.'</w:tbl>';
    }

    private function styles(): string
    {
        $style = fn (string $id, string $name, string $run, string $paragraph = '') => '<w:style w:type="paragraph" w:styleId="'.$id.'"><w:name w:val="'.$name.'"/><w:basedOn w:val="Normal"/><w:next w:val="Normal"/>'
            .($paragraph ? '<w:pPr>'.$paragraph.'</w:pPr>' : '').'<w:rPr>'.$run.'</w:rPr></w:style>';

        return $this->xml('<w:styles xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main">'
            .'<w:docDefaults><w:rPrDefault><w:rPr><w:rFonts w:ascii="Calibri" w:hAnsi="Calibri" w:cs="Calibri"/><w:sz w:val="22"/><w:lang w:val="fr-FR"/></w:rPr></w:rPrDefault>'
            .'<w:pPrDefault><w:pPr><w:spacing w:after="120" w:line="276" w:lineRule="auto"/></w:pPr></w:pPrDefault></w:docDefaults>'
            .'<w:style w:type="paragraph" w:default="1" w:styleId="Normal"><w:name w:val="Normal"/></w:style>'
            .$style('Title', 'Title', '<w:b/><w:sz w:val="40"/><w:color w:val="077A3F"/>')
            .$style('Subtitle', 'Subtitle', '<w:color w:val="57534E"/>', '<w:spacing w:after="240"/>')
            .$style('Heading1', 'heading 1', '<w:b/><w:sz w:val="28"/>', '<w:keepNext/><w:spacing w:before="360" w:after="120"/><w:outlineLvl w:val="0"/>')
            .$style('Note', 'Note', '<w:i/><w:sz w:val="18"/><w:color w:val="57534E"/>', '<w:spacing w:before="360"/>')
            .'</w:styles>');
    }

    private function contentTypes(): string
    {
        return $this->xml('<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/>'
            .'<Override PartName="/word/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.styles+xml"/>'
            .'<Override PartName="/docProps/core.xml" ContentType="application/vnd.openxmlformats-package.core-properties+xml"/>'
            .'</Types>');
    }

    private function packageRelationships(): string
    {
        return $this->xml('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/package/2006/relationships/metadata/core-properties" Target="docProps/core.xml"/>'
            .'</Relationships>');
    }

    private function documentRelationships(): string
    {
        return $this->xml('<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>');
    }

    private function coreProperties(Project $project): string
    {
        return $this->xml('<cp:coreProperties xmlns:cp="http://schemas.openxmlformats.org/package/2006/metadata/core-properties" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:dcterms="http://purl.org/dc/terms/" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance">'
            .'<dc:title>'.$this->escape('Business plan · '.$project->title).'</dc:title>'
            .'<dc:creator>IDEE</dc:creator><dc:language>fr-FR</dc:language>'
            .'<dcterms:created xsi:type="dcterms:W3CDTF">'.now()->utc()->format('Y-m-d\TH:i:s\Z').'</dcterms:created>'
            .'</cp:coreProperties>');
    }

    private function xml(string $body): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'."\n".$body;
    }

    /**
     * Échappe le texte et retire les caractères de contrôle que le XML refuse.
     */
    private function escape(string $text): string
    {
        $clean = (string) preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}]/u', '', $text);

        return htmlspecialchars($clean, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
