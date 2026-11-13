<?php
/**
 * Quince
 *
 * @author    Quince Project <quinceproject@proton.me>
 * @copyright Since 2026 Marcin Gajewski
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use Quince\Slider\Settings\Slides;

/**
 * Quince Slider: pas zapowiedzi na górze strony.
 *
 * Slajd nie wie, gdzie stanie: moduł wystawia hook `displayQuinceHomeSlider`,
 * a strona główna silnika wstawia go w kolumnę sekcji. Dzięki temu slajder da
 * się przestawić albo zdjąć bez ruszania tego modułu, a sklep, który chce dwa
 * pasy w różnych miejscach, dostanie je bez zmiany w motywie.
 *
 * Wygląd slajdu należy do motywu (`themes/quince/assets/css/components/slider.css`).
 * Moduł dostarcza wyłącznie treść i klasy, które ten komponent rozumie.
 */
class Quince_Slider extends Module
{
    public function __construct()
    {
        $this->name = 'quince_slider';
        $this->tab = 'front_office_features';
        $this->version = '0.1.2';
        $this->author = 'Quince Project';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => '9.99.99'];
        $this->bootstrap = true;

        // Ekran modułu dziedziczy po kontrolerze silnika, a szablon po jego
        // layoucie. Bez tej deklaracji PrestaShop pozwoli wyłączyć silnik,
        // a moduł przestanie otwierać swój ekran bez żadnego ostrzeżenia.
        $this->dependencies = ['quince_engine'];

        $this->tabs = [
            [
                'class_name' => 'AdminQuinceSlider',
                'visible' => true,
                'name' => 'Slider',
                'parent_class_name' => 'AdminQuinceProject',
                'route_name' => 'quince_slider',
                'wording' => 'Slider',
                'wording_domain' => 'Modules.Quinceslider.Admin',
            ],
        ];

        parent::__construct();

        $this->displayName = $this->trans('Quince slider', [], 'Modules.Quinceslider.Admin');
        $this->description = $this->trans(
            'A band of slides for the top of the page: colour, gradient, photo or video.',
            [],
            'Modules.Quinceslider.Admin'
        );
        $this->confirmUninstall = $this->trans(
            'Uninstall the module? The slides will be removed.',
            [],
            'Modules.Quinceslider.Admin'
        );
    }

    public function install(): bool
    {
        return parent::install()
            && (new Slides())->installDefaults()
            && $this->registerHook('actionQuinceRegisterFeature')
            && $this->registerSliderHook();
    }

    public function uninstall(): bool
    {
        return (new Slides())->removeSettings() && parent::uninstall();
    }

    /**
     * Własny hook, w który wstawia się pas.
     *
     * `Hook::add()` musi pójść PRZED rejestracją: PrestaShop nie zakłada
     * wpisu w `ps_hook` sam, a rejestracja na nieistniejący hook kończy się
     * ciszą: moduł jest zainstalowany, a na stronie nie ma nic.
     */
    private function registerSliderHook(): bool
    {
        $nazwa = 'displayQuinceHomeSlider';

        if (!Hook::getIdByName($nazwa)) {
            $hook = new Hook();
            $hook->name = $nazwa;
            $hook->title = 'Quince: home slider';
            $hook->description = 'Pas zapowiedzi wstawiany w kolumnę sekcji strony głównej.';
            $hook->position = 1;

            if (!$hook->add()) {
                return false;
            }
        }

        return $this->registerHook($nazwa);
    }

    /**
     * Zgłoszenie do silnika: co dostarczamy i gdzie tego szukać.
     *
     * Bez tego moduł działałby, ale panel silnika nie wiedziałby o jego ekranie.
     *
     * @return array<int,array{type:string,id:string,label:string,route:string}>
     */
    public function hookActionQuinceRegisterFeature(): array
    {
        return [[
            'type' => 'home_slider',
            'id' => 'quince_slider',
            'label' => $this->trans('Slider', [], 'Modules.Quinceslider.Admin'),
            'route' => 'quince_slider',
        ]];
    }

    /** Pas zapowiedzi. Pusty, dopóki sklep nie włączy ani jednego slajdu. */
    public function hookDisplayQuinceHomeSlider(): string
    {
        $slides = new Slides();
        $slajdy = $slides->forTemplate();

        if ($slajdy === []) {
            return '';
        }

        $jezyk = (int) $this->context->language->id;
        $ustawienia = $slides->settings();

        $this->smarty->assign([
            'quince_slides' => $this->forSmarty($slajdy, $jezyk),
            'quince_slider' => $ustawienia,
        ]);

        return $this->display(__FILE__, 'views/templates/hook/slider.tpl');
    }

    /**
     * Slajdy przełożone na język odwiedzającego.
     *
     * Szablon dostaje gotowe napisy zamiast map językowych: wybór języka to
     * zadanie modułu, a nie szablonu, który musiałby powtarzać tę samą logikę
     * przy każdym napisie.
     *
     * @param array<int,array<string,mixed>> $slajdy
     *
     * @return array<int,array<string,mixed>>
     */
    private function forSmarty(array $slajdy, int $jezyk): array
    {
        foreach ($slajdy as $i => $slajd) {
            $slajdy[$i]['over'] = Slides::text($slajd['over'], $jezyk);
            $slajdy[$i]['title'] = Slides::text($slajd['title'], $jezyk);
            $slajdy[$i]['text'] = Slides::text($slajd['text'], $jezyk);

            foreach ($slajd['specs'] as $j => $pigulka) {
                $slajdy[$i]['specs'][$j] = Slides::text($pigulka, $jezyk);
            }

            foreach ($slajd['buttons'] as $j => $przycisk) {
                $slajdy[$i]['buttons'][$j]['label'] = Slides::text($przycisk['label'], $jezyk);
            }

            if (($slajd['background'] ?? '') === Slides::BG_VIDEO) {
                $slajdy[$i]['embed'] = $this->embed((string) ($slajd['video'] ?? ''));
            }

            if (($slajd['source'] ?? '') === Slides::SOURCE_PRODUCT) {
                $slajdy[$i] = $this->zProduktu($slajdy[$i], $jezyk);
            }
        }

        return $slajdy;
    }

    /**
     * Slajd o produkcie: puste pola wypełnia katalog.
     *
     * Wpisane ręcznie zostaje nietknięte: sklep bywa, że chce powiedzieć
     * „Zestaw dla warsztatu" zamiast pełnej nazwy katalogowej. Reszta idzie
     * za produktem, więc zmiana ceny w katalogu jest widoczna na slajdzie bez
     * wchodzenia tutaj.
     *
     * @param array<string,mixed> $slajd
     *
     * @return array<string,mixed>
     */
    private function zProduktu(array $slajd, int $jezyk): array
    {
        $id = (int) ($slajd['product'] ?? 0);

        if ($id <= 0) {
            return $slajd;
        }

        $produkt = new Product($id, false, $jezyk);

        if (!Validate::isLoadedObject($produkt)) {
            return $slajd;
        }

        if ($slajd['title'] === '') {
            $slajd['title'] = (string) $produkt->name;
        }

        if ($slajd['text'] === '') {
            $slajd['text'] = strip_tags((string) $produkt->description_short);
        }

        // Cena z podatkiem i w walucie odwiedzającego: slajd pokazuje to samo,
        // co zobaczy na karcie produktu.
        $locale = $this->context->getCurrentLocale();
        $waluta = $this->context->currency->iso_code;

        if ($slajd['price_now'] === '' && $locale !== null) {
            $slajd['price_now'] = $locale->formatPrice((float) $produkt->getPrice(true, null), $waluta);

            $bez = (float) $produkt->getPrice(true, null, 6, null, false, false);
            $z = (float) $produkt->getPrice(true, null);

            if ($slajd['price_was'] === '' && $bez > $z) {
                $slajd['price_was'] = $locale->formatPrice($bez, $waluta);
            }
        }

        if ($slajd['media'] === '') {
            $okladka = Product::getCover($id);

            if (!empty($okladka['id_image'])) {
                $slajd['media'] = $this->context->link->getImageLink(
                    (string) $produkt->link_rewrite,
                    (string) $okladka['id_image'],
                    'large_default'
                );
            }
        }

        // Przycisk bez adresu prowadzi na kartę produktu: slajd o produkcie
        // ma jeden oczywisty cel i nie ma po co go wpisywać.
        foreach ($slajd['buttons'] as $j => $przycisk) {
            if ($przycisk['url'] === '') {
                $slajd['buttons'][$j]['url'] = $this->context->link->getProductLink($produkt);
            }
        }

        return $slajd;
    }

    /**
     * Adres osadzenia filmu.
     *
     * Sklep wkleja zwykły odnośnik z YouTube albo Vimeo, a nie adres ramki;
     * tego drugiego nie ma skąd wziąć bez wchodzenia w kod strony. Parametry
     * wyciszają film i gaszą sterowanie: slajd jest tłem, nie odtwarzaczem.
     */
    private function embed(string $adres): string
    {
        if (preg_match('~(?:youtube\.com/.*[?&]v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,})~', $adres, $m)) {
            return 'https://www.youtube-nocookie.com/embed/' . $m[1]
                . '?autoplay=1&mute=1&controls=0&loop=1&playsinline=1&modestbranding=1&rel=0&playlist=' . $m[1];
        }

        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $adres, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1]
                . '?autoplay=1&muted=1&loop=1&background=1';
        }

        return '';
    }

    /**
     * Przycisk „Konfiguruj" w menedżerze modułów.
     *
     * PrestaShop pokazuje go po samym istnieniu tej metody, a my przenosimy
     * sprzedawcę na ekran modułu w panelu.
     */
    public function getContent(): void
    {
        \Tools::redirectAdmin($this->get('router')->generate('quince_slider'));
    }
}
