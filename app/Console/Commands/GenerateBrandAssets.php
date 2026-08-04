<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Génère les déclinaisons raster du sceau depuis sa définition géométrique.
 *
 * Le SVG suffit aux navigateurs modernes, mais il en faut une version
 * bitmap pour le favicon .ico, l'icône iOS, l'aperçu réseaux sociaux et
 * surtout le PDF (DomPDF ne rasterise pas le SVG).
 *
 * Le dessin est fait 8× trop grand puis réduit : c'est ce sur-échantillonnage
 * qui donne des bords lisses, GD ne sachant pas antialiaser les formes pleines.
 */
class GenerateBrandAssets extends Command
{
    protected $signature = 'brand:assets';

    protected $description = 'Regénère favicon.ico, les icônes PNG et le logo du PDF depuis le sceau Audit Master';

    private const SS = 8;              // facteur de sur-échantillonnage
    private const GRID = 64;           // grille de référence du sceau

    private const NAVY = [0, 51, 102];
    private const YELLOW = [255, 215, 0];

    /**
     * Couleur des barres déjà fusionnée avec le fond du sceau.
     *
     * Dessiner en semi-transparent produirait des surépaisseurs : un rectangle
     * arrondi est composé de deux rectangles et quatre disques, et GD
     * recompose l'alpha à chaque recouvrement — les angles ressortaient en
     * pastilles plus foncées. Le SVG, lui, aplatit la même teinte en un seul
     * passage : ces valeurs sont l'équivalent opaque de son rendu.
     */
    private const BAR_ON_NAVY = [56, 96, 136];    // blanc 22 % sur #003366
    private const BAR_ON_YELLOW = [199, 179, 22]; // bleu 22 % sur #FFD700

    /** Géométrie du sceau sur la grille 64×64, partagée avec le SVG. */
    private const BARS = [[14, 45, 9, 9], [27, 39, 9, 15], [40, 33, 9, 21]];

    private const CHECK = [[16, 30], [26, 40], [48, 15]];

    public function handle(): int
    {
        if (! extension_loaded('gd')) {
            $this->error('L\'extension GD est requise.');

            return self::FAILURE;
        }

        $out = public_path('images');

        if (! is_dir($out)) {
            mkdir($out, 0o775, true);
        }

        // Icônes de navigateur / système
        $this->writePng($this->render(16, false), public_path('favicon-16.png'));
        $this->writePng($this->render(32, false), public_path('favicon-32.png'));
        $this->writePng($this->render(180, false, [255, 255, 255]), public_path('apple-touch-icon.png'));
        $this->writePng($this->render(512, false), public_path('images/logo-mark-512.png'));

        // Logos du PDF : fond opaque, DomPDF gère mal la transparence PNG.
        // Le fond est celui de la zone où l'image sera posée, sinon le sceau
        // apparaît cerné d'un halo blanc — visible sur le bandeau de couverture.
        $this->writePng($this->render(256, false, [255, 255, 255]), public_path('images/logo-pdf.png'));
        $this->writePng($this->render(256, true, self::NAVY), public_path('images/logo-pdf-band.png'));

        // Bannière pour l'e-mail et l'aperçu réseaux sociaux
        $this->writePng($this->banner(1200, 630), public_path('images/og-image.png'));

        $this->writeIco(
            [$this->render(16, false), $this->render(32, false), $this->render(48, false)],
            public_path('favicon.ico')
        );

        $this->info('Déclinaisons du sceau générées dans /public.');

        return self::SUCCESS;
    }

    /**
     * Dessine le sceau à la taille demandée.
     *
     * @param  array{0:int,1:int,2:int}|null  $background  fond opaque, ou null pour la transparence
     */
    private function render(int $size, bool $reversed, ?array $background = null): \GdImage
    {
        $big = $size * self::SS;
        $im = $this->canvas($big, $big);
        $scale = $big / self::GRID;

        $sealColor = $reversed ? self::YELLOW : self::NAVY;
        $checkColor = $reversed ? self::NAVY : self::YELLOW;

        if ($background !== null) {
            imagefilledrectangle($im, 0, 0, $big, $big, imagecolorallocate($im, ...$background));
        }

        $seal = imagecolorallocate($im, ...$sealColor);
        $this->roundedRect($im, 2 * $scale, 2 * $scale, 60 * $scale, 60 * $scale, 15 * $scale, $seal);

        $barColor = imagecolorallocate($im, ...($reversed ? self::BAR_ON_YELLOW : self::BAR_ON_NAVY));

        foreach (self::BARS as [$x, $y, $w, $h]) {
            $this->roundedRect($im, $x * $scale, $y * $scale, $w * $scale, $h * $scale, 2 * $scale, $barColor);
        }

        // La coche, tracée en tamponnant des disques : c'est ce qui donne des
        // extrémités et un angle arrondis, que GD ne sait pas produire seul.
        $check = imagecolorallocate($im, ...$checkColor);
        $radius = 4 * $scale;
        [[$ax, $ay], [$bx, $by], [$cx, $cy]] = self::CHECK;
        $this->stamp($im, $ax * $scale, $ay * $scale, $bx * $scale, $by * $scale, $radius, $check);
        $this->stamp($im, $bx * $scale, $by * $scale, $cx * $scale, $cy * $scale, $radius, $check);

        return $this->downscale($im, $size);
    }

    /** Bannière horizontale sceau + nom, pour l'e-mail et les partages. */
    private function banner(int $width, int $height): \GdImage
    {
        $im = $this->canvas($width, $height);
        $navy = imagecolorallocate($im, ...self::NAVY);
        $yellow = imagecolorallocate($im, ...self::YELLOW);
        $white = imagecolorallocate($im, 255, 255, 255);

        imagefilledrectangle($im, 0, 0, $width, $height, $navy);
        imagefilledrectangle($im, 0, $height - 16, $width, $height, $yellow);

        $mark = $this->render(240, true, self::NAVY);
        imagecopy($im, $mark, 120, (int) (($height - 240) / 2) - 30, 0, 0, 240, 240);
        imagedestroy($mark);

        $font = public_path('fonts/SpecialElite-Regular.ttf');

        if (is_file($font)) {
            imagettftext($im, 78, 0, 420, (int) ($height / 2) - 4, $white, $font, 'NJIEZM');
            imagettftext($im, 30, 0, 424, (int) ($height / 2) + 62, $yellow, $font, 'AUDIT MASTER');
        }

        return $im;
    }

    private function canvas(int $w, int $h): \GdImage
    {
        $im = imagecreatetruecolor($w, $h);
        imagealphablending($im, false);
        imagefilledrectangle($im, 0, 0, $w, $h, imagecolorallocatealpha($im, 0, 0, 0, 127));
        imagealphablending($im, true);

        return $im;
    }

    private function downscale(\GdImage $im, int $size): \GdImage
    {
        imagealphablending($im, false);
        imagesavealpha($im, true);

        $small = imagescale($im, $size, $size, IMG_BICUBIC);
        imagealphablending($small, false);
        imagesavealpha($small, true);
        imagedestroy($im);

        return $small;
    }

    private function roundedRect(\GdImage $im, float $x, float $y, float $w, float $h, float $r, int $color): void
    {
        $r = min($r, $w / 2, $h / 2);
        $x2 = $x + $w;
        $y2 = $y + $h;

        imagefilledrectangle($im, (int) round($x + $r), (int) round($y), (int) round($x2 - $r), (int) round($y2), $color);
        imagefilledrectangle($im, (int) round($x), (int) round($y + $r), (int) round($x2), (int) round($y2 - $r), $color);

        $d = (int) round($r * 2);

        foreach ([[$x + $r, $y + $r], [$x2 - $r, $y + $r], [$x + $r, $y2 - $r], [$x2 - $r, $y2 - $r]] as [$cx, $cy]) {
            imagefilledellipse($im, (int) round($cx), (int) round($cy), $d, $d, $color);
        }
    }

    private function stamp(\GdImage $im, float $x1, float $y1, float $x2, float $y2, float $radius, int $color): void
    {
        $length = hypot($x2 - $x1, $y2 - $y1);
        $steps = max(1, (int) ceil($length));
        $d = (int) round($radius * 2);

        for ($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            imagefilledellipse(
                $im,
                (int) round($x1 + ($x2 - $x1) * $t),
                (int) round($y1 + ($y2 - $y1) * $t),
                $d,
                $d,
                $color
            );
        }
    }

    private function writePng(\GdImage $im, string $path): void
    {
        imagesavealpha($im, true);
        imagepng($im, $path, 9);
        imagedestroy($im);
        $this->line('  '.str_replace(public_path(), 'public', $path));
    }

    /**
     * Conteneur ICO encapsulant des PNG (format accepté depuis Vista) :
     * plus simple et plus net qu'un empilement de bitmaps DIB.
     *
     * @param  array<int, \GdImage>  $images
     */
    private function writeIco(array $images, string $path): void
    {
        $entries = '';
        $payload = '';
        $offset = 6 + (16 * count($images));

        foreach ($images as $im) {
            imagesavealpha($im, true);

            ob_start();
            imagepng($im, null, 9);
            $png = ob_get_clean();

            $size = imagesx($im);
            imagedestroy($im);

            $entries .= pack(
                'CCCCvvVV',
                $size >= 256 ? 0 : $size,   // largeur (0 = 256)
                $size >= 256 ? 0 : $size,   // hauteur
                0,                          // palette
                0,                          // réservé
                1,                          // plans
                32,                         // bits par pixel
                strlen($png),
                $offset
            );

            $payload .= $png;
            $offset += strlen($png);
        }

        file_put_contents($path, pack('vvv', 0, 1, count($images)).$entries.$payload);
        $this->line('  public/favicon.ico');
    }
}
