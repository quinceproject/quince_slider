<?php
/**
 * Quince
 *
 * @author    Quince Project <quinceproject@proton.me>
 * @copyright Since 2026 Marcin Gajewski
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace Quince\Slider\Settings;

use Configuration;
use Quince\Engine\Settings\ConfigStore;

/**
 * Slajdy i ustawienia slajdera.
 *
 * Model odwzorowuje komponent motywu (`themes/quince/assets/css/components/slider.css`)
 * i nic ponadto: każde pole tutaj ma po drugiej stronie klasę albo zmienną,
 * którą szablon ustawia na slajdzie. Pole bez odpowiednika w komponencie byłoby
 * ustawieniem, które niczego nie zmienia.
 *
 * Slajd składa się z trzech rzeczy niezależnych od siebie:
 *
 *   TŁO       barwa, gradient, zdjęcie albo film. Zdjęcie dostaje wtopienia
 *             przy krawędziach, film — planszę na czas wczytywania.
 *   TREŚĆ     nagłówek, zdanie, pigułki, cena, przyciski, licznik. Każde
 *             z osobna, bo slajd zapowiadający promocję ma cenę i licznik,
 *             a slajd marki ani jednego, ani drugiego.
 *   UŁOŻENIE  na całej szerokości albo w panelu przy krawędzi, do lewej albo
 *             na środku, ze zdjęciem obok treści albo bez.
 *
 * Zapis idzie w JSON jednym kluczem, bo liczba slajdów zmienia się przy każdym
 * dołożeniu — tak samo jak sekcje strony głównej.
 */
final class Slides
{
    private const PREFIX = 'QUINCE_SLIDER_';

    /** Rodzaje tła. Każdy ma własny komplet pól i własną klasę w komponencie. */
    public const BG_SOLID = 'solid';
    public const BG_GRADIENT = 'gradient';
    public const BG_PHOTO = 'photo';
    public const BG_VIDEO = 'video';

    /** @var array<string,string> */
    public const BACKGROUNDS = [
        self::BG_SOLID => 'Colour',
        self::BG_GRADIENT => 'Gradient',
        self::BG_PHOTO => 'Photo',
        self::BG_VIDEO => 'Video',
    ];

    /**
     * Wtopienie krawędzi zdjęcia.
     *
     * Zdjęcie dochodzące ostrą krawędzią do brzegu pasa wygląda jak wklejone.
     * Wtopienie rozmywa je w tło sekcji po wskazanej stronie.
     */
    public const FADES = [
        'none' => 'None',
        'start' => 'On the left',
        'end' => 'On the right',
        'x' => 'On both sides',
        'bottom' => 'At the bottom',
    ];

    /** Treść na całej szerokości albo w panelu przy krawędzi. */
    public const LAYOUTS = [
        'full' => 'Across the slide',
        'split' => 'In a panel',
    ];

    /** Do której krawędzi równa się treść. */
    public const ALIGNMENTS = [
        'start' => 'Left',
        'center' => 'Centred',
    ];

    /** Wysokość pasa. Wartości odpowiadają tokenom `--q-slider-height-*`. */
    public const HEIGHTS = [
        'small' => 'Low',
        'medium' => 'Medium',
        'large' => 'Tall',
    ];

    /** Pas przez całą szerokość okna albo blok w świetle strony. */
    public const WIDTHS = [
        'full' => 'Full width',
        'boxed' => 'Page width',
    ];

    /** Postać przycisku na slajdzie: ta sama, co w całym motywie. */
    public const BUTTON_STYLES = [
        'primary' => 'Filled',
        'secondary' => 'Outlined',
        'quiet' => 'Plain',
    ];

    /**
     * Skąd slajd bierze treść.
     *
     * Slajd zapowiadający produkt nie ma po co powtarzać jego nazwy, ceny
     * i zdjęcia: wszystko to jest w katalogu i zmienia się razem z nim.
     * Przepisane ręcznie rozjeżdża się przy pierwszej zmianie ceny.
     */
    public const SOURCE_TEXT = 'text';
    public const SOURCE_PRODUCT = 'product';

    /** @var array<string,string> */
    public const SOURCES = [
        self::SOURCE_TEXT => 'Own text',
        self::SOURCE_PRODUCT => 'A product',
    ];

    /** Dwa przyciski to granica czytelności: trzeci przestaje być wyborem. */
    public const MAX_BUTTONS = 2;

    /** Tyle pigułek mieści się w jednym rzędzie pod zdaniem. */
    public const MAX_SPECS = 4;

    /** Co ile sekund slajder przechodzi dalej, zanim sklep to zmieni. */
    private const DEFAULT_INTERVAL = 6;

    private const SCHEMA = [
        'SLIDES' => '',
        'HEIGHT' => 'medium',
        'WIDTH' => 'full',
        'AUTOPLAY' => '1',
        'INTERVAL' => '6',
        'ARROWS' => '1',
        'DOTS' => '1',
    ];

    /**
     * Ustawienia całego pasa, wspólne dla wszystkich slajdów.
     *
     * @return array<string,string>
     */
    public function settings(): array
    {
        $ustawienia = [];

        foreach (self::SCHEMA as $nazwa => $domyslna) {
            if ($nazwa === 'SLIDES') {
                continue;
            }

            $zapis = Configuration::get(self::PREFIX . $nazwa);
            $ustawienia[$nazwa] = $zapis === false || $zapis === null ? $domyslna : (string) $zapis;
        }

        return $ustawienia;
    }

    /**
     * Slajdy w kolejności, w jakiej mają jechać.
     *
     * @return array<int,array<string,mixed>>
     */
    public function all(): array
    {
        $zapis = (string) Configuration::get(self::PREFIX . 'SLIDES');

        if (trim($zapis) === '') {
            return [];
        }

        $dane = json_decode($zapis, true);

        return is_array($dane) ? $this->sanitize($dane) : [];
    }

    /**
     * Slajdy gotowe do wyrenderowania: bez wyłączonych i bez pustych.
     *
     * Slajd bez nagłówka i bez przycisku nie niesie żadnej treści, więc pas
     * z takim slajdem przewijałby się przez pustą planszę.
     *
     * @return array<int,array<string,mixed>>
     */
    public function forTemplate(): array
    {
        $gotowe = [];

        foreach ($this->all() as $slajd) {
            if (empty($slajd['active'])) {
                continue;
            }

            if ($slajd['title'] === [] && $slajd['buttons'] === [] && $slajd['background'] !== self::BG_VIDEO) {
                continue;
            }

            $gotowe[] = $slajd;
        }

        return $gotowe;
    }

    /**
     * Zapis z wysyłki ekranu.
     *
     * @param array<string,mixed> $values
     */
    public function save(array $values): void
    {
        if (isset($values['SLIDES'])) {
            $dane = json_decode((string) $values['SLIDES'], true);

            // Zapis idzie przez ConfigStore, a nie wprost: w sklepie z wieloma
            // witrynami wartosc zapisana w kontekscie wszystkich sklepow nie
            // dociera do tego, ktory ma wlasny wiersz w `ps_configuration`.
            // Slajdy zapisywaly sie wtedy do wiersza globalnego, a sklep
            // czytal swoj pusty i pas znikal bez zadnego bledu.
            ConfigStore::set(
                self::PREFIX . 'SLIDES',
                (string) json_encode(is_array($dane) ? $this->sanitize($dane) : []),
                true
            );
        }

        ConfigStore::set(self::PREFIX . 'HEIGHT', $this->jednaZ($values['HEIGHT'] ?? '', self::HEIGHTS, 'medium'));
        ConfigStore::set(self::PREFIX . 'WIDTH', $this->jednaZ($values['WIDTH'] ?? '', self::WIDTHS, 'full'));
        ConfigStore::set(self::PREFIX . 'AUTOPLAY', empty($values['AUTOPLAY']) ? '0' : '1');
        ConfigStore::set(self::PREFIX . 'ARROWS', empty($values['ARROWS']) ? '0' : '1');
        ConfigStore::set(self::PREFIX . 'DOTS', empty($values['DOTS']) ? '0' : '1');

        // Poniżej dwóch sekund slajd nie zdąży zostać przeczytany, powyżej
        // trzydziestu pas wygląda na nieruchomy.
        $odstep = (int) ($values['INTERVAL'] ?? self::DEFAULT_INTERVAL);
        ConfigStore::set(self::PREFIX . 'INTERVAL', (string) max(2, min($odstep, 30)));
    }

    /**
     * Slajdy z wysyłki, obcięte do tego, co komponent potrafi postawić.
     *
     * @param array<int,mixed> $dane
     *
     * @return array<int,array<string,mixed>>
     */
    public function sanitize(array $dane): array
    {
        $slajdy = [];

        foreach ($dane as $wpis) {
            if (!is_array($wpis)) {
                continue;
            }

            $tlo = $this->jednaZ($wpis['background'] ?? '', self::BACKGROUNDS, self::BG_SOLID);
            $zrodlo = $this->jednaZ($wpis['source'] ?? '', self::SOURCES, self::SOURCE_TEXT);

            $slajd = [
                'id' => $this->slug((string) ($wpis['id'] ?? ''), 'slide-' . (count($slajdy) + 1)),
                'active' => !empty($wpis['active']),
                'background' => $tlo,

                // Ułożenie i jasność tekstu są niezależne od tła: biały napis
                // bywa potrzebny i na zdjęciu, i na barwie marki.
                'layout' => $this->jednaZ($wpis['layout'] ?? '', self::LAYOUTS, 'full'),
                'align' => $this->jednaZ($wpis['align'] ?? '', self::ALIGNMENTS, 'start'),
                'light' => !empty($wpis['light']),
                'shade' => !empty($wpis['shade']),

                // Przy slajdzie o produkcie pola treści zostają puste, dopóki
                // sklep nie chce powiedzieć czegoś innego niż katalog.
                'source' => $zrodlo,
                'product' => max(0, (int) ($wpis['product'] ?? 0)),

                // Nadtytuł: z jakiego działu jest ta zapowiedź.
                'over' => $this->i18n($wpis['over'] ?? []),
                'title' => $this->i18n($wpis['title'] ?? []),
                'text' => $this->i18n($wpis['text'] ?? []),
                'specs' => $this->specs($wpis['specs'] ?? []),
                'buttons' => $this->buttons($wpis['buttons'] ?? [], $zrodlo),

                'price_now' => trim((string) ($wpis['price_now'] ?? '')),
                'price_was' => trim((string) ($wpis['price_was'] ?? '')),

                // Licznik gaśnie sam po terminie, więc data jest jedynym
                // ustawieniem, jakiego potrzebuje.
                'timer_to' => $this->data((string) ($wpis['timer_to'] ?? '')),

                // Zdjęcie obok treści. Udział liczony w procentach szerokości
                // slajdu, tak jak `--q-slide-media-share` w komponencie.
                'media' => trim((string) ($wpis['media'] ?? '')),
                'media_share' => max(20, min((int) ($wpis['media_share'] ?? 40), 60)),
            ];

            $slajdy[] = array_merge($slajd, $this->tlo($wpis, $tlo));
        }

        return $slajdy;
    }

    /**
     * Pola należące do wybranego rodzaju tła. Pozostałe nie są zapisywane:
     * slajd na barwie nie ma po co pamiętać adresu filmu.
     *
     * @param array<string,mixed> $wpis
     *
     * @return array<string,mixed>
     */
    private function tlo(array $wpis, string $tlo): array
    {
        if ($tlo === self::BG_GRADIENT) {
            return [
                'from' => $this->barwa((string) ($wpis['from'] ?? '')),
                'to' => $this->barwa((string) ($wpis['to'] ?? '')),
                // Kąt liczony tak jak w CSS: zero to z dołu do góry.
                'angle' => max(0, min((int) ($wpis['angle'] ?? 120), 360)),
            ];
        }

        if ($tlo === self::BG_PHOTO) {
            return [
                'image' => trim((string) ($wpis['image'] ?? '')),
                'fade' => $this->jednaZ($wpis['fade'] ?? '', self::FADES, 'none'),
                'bg' => $this->barwa((string) ($wpis['bg'] ?? '')),
            ];
        }

        if ($tlo === self::BG_VIDEO) {
            return [
                // Adres z YouTube albo Vimeo. Osadzenie składa szablon, bo to
                // on wie, jakich parametrów potrzebuje odtwarzacz.
                'video' => trim((string) ($wpis['video'] ?? '')),
                'poster' => trim((string) ($wpis['poster'] ?? '')),
            ];
        }

        return ['bg' => $this->barwa((string) ($wpis['bg'] ?? ''))];
    }

    /**
     * Pigułki pod zdaniem.
     *
     * @param mixed $dane
     *
     * @return array<int,array<int,string>>
     */
    private function specs($dane): array
    {
        if (!is_array($dane)) {
            return [];
        }

        $pigulki = [];

        foreach (array_slice(array_values($dane), 0, self::MAX_SPECS) as $pigulka) {
            $napisy = $this->i18n($pigulka);

            if ($napisy !== []) {
                $pigulki[] = $napisy;
            }
        }

        return $pigulki;
    }

    /**
     * Przyciski slajdu.
     *
     * Przycisk bez napisu jest pustą ramką, więc o istnieniu pozycji rozstrzyga
     * napis. Adres może być pusty TYLKO przy slajdzie o produkcie: wtedy
     * prowadzi na jego kartę i nie ma po co go wpisywać.
     *
     * @param mixed $dane
     *
     * @return array<int,array<string,mixed>>
     */
    private function buttons($dane, string $zrodlo): array
    {
        if (!is_array($dane)) {
            return [];
        }

        $przyciski = [];

        foreach (array_slice(array_values($dane), 0, self::MAX_BUTTONS) as $przycisk) {
            if (!is_array($przycisk)) {
                continue;
            }

            $napis = $this->i18n($przycisk['label'] ?? []);
            $adres = trim((string) ($przycisk['url'] ?? ''));

            if ($napis === [] || ($adres === '' && $zrodlo !== self::SOURCE_PRODUCT)) {
                continue;
            }

            $przyciski[] = [
                'label' => $napis,
                'url' => $adres,
                'style' => $this->jednaZ($przycisk['style'] ?? '', self::BUTTON_STYLES, 'primary'),
            ];
        }

        return $przyciski;
    }

    /**
     * Napisy w językach sklepu.
     *
     * @param mixed $dane
     *
     * @return array<int,string>
     */
    private function i18n($dane): array
    {
        if (!is_array($dane)) {
            return [];
        }

        $napisy = [];

        foreach ($dane as $jezyk => $tekst) {
            $tresc = trim((string) $tekst);

            if ((int) $jezyk > 0 && $tresc !== '') {
                $napisy[(int) $jezyk] = $tresc;
            }
        }

        return $napisy;
    }

    /**
     * Napis w języku odwiedzającego, a gdy go brak, w pierwszym wypełnionym.
     *
     * @param array<int,string> $napisy
     */
    public static function text(array $napisy, int $jezyk, string $zapas = ''): string
    {
        if (isset($napisy[$jezyk]) && $napisy[$jezyk] !== '') {
            return $napisy[$jezyk];
        }

        foreach ($napisy as $tekst) {
            if ($tekst !== '') {
                return $tekst;
            }
        }

        return $zapas;
    }

    /**
     * Barwa z palety sklepu albo kod szesnastkowy.
     *
     * Nazwa skali musi byc jedna z czterech, ktore wypisuje silnik. Dowolne
     * slowo przechodzilo walidacje i konczylo sie odwolaniem do zmiennej,
     * ktorej nie ma: slajd mial wtedy tlo przezroczyste zamiast barwnego,
     * bez zadnego bledu.
     */
    private function barwa(string $wartosc): string
    {
        $wartosc = trim($wartosc);

        return preg_match('/^(#[0-9a-fA-F]{3,8}|(neutral|primary|secondary|promo)-([1-9]|1[0-2]))$/', $wartosc) === 1
            ? $wartosc
            : '';
    }

    /** Data i godzina w postaci, którą rozumie pole formularza i licznik. */
    private function data(string $wartosc): string
    {
        $wartosc = trim($wartosc);

        return preg_match('/^\d{4}-\d{2}-\d{2}([ T]\d{2}:\d{2})?$/', $wartosc) === 1 ? $wartosc : '';
    }

    /** @param array<string,string> $dozwolone */
    private function jednaZ($wartosc, array $dozwolone, string $zapas): string
    {
        return isset($dozwolone[(string) $wartosc]) ? (string) $wartosc : $zapas;
    }

    private function slug(string $wartosc, string $zapas): string
    {
        $czysty = preg_replace('/[^a-z0-9_-]/', '', strtolower($wartosc));

        return $czysty !== '' ? $czysty : $zapas;
    }

    /**
     * Klucze, które moduł zakłada przy instalacji i kasuje przy usunięciu.
     *
     * @return string[]
     */
    public static function configurationKeys(): array
    {
        $klucze = [];

        foreach (array_keys(self::SCHEMA) as $nazwa) {
            $klucze[] = self::PREFIX . $nazwa;
        }

        return $klucze;
    }

    /** Wartości wyjściowe zapisywane przy instalacji. */
    public function installDefaults(): bool
    {
        foreach (self::SCHEMA as $nazwa => $wartosc) {
            ConfigStore::set(self::PREFIX . $nazwa, $wartosc, $nazwa === 'SLIDES');
        }

        return true;
    }

    /** Sprzątanie przy usunięciu modułu. */
    public function removeSettings(): bool
    {
        foreach (self::configurationKeys() as $klucz) {
            Configuration::deleteByName($klucz);
        }

        return true;
    }
}
