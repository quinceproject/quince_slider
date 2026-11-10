{**
 * Quince — pas zapowiedzi.
 *
 * Wygląd należy do motywu: `themes/quince/assets/css/components/slider.css`.
 * Ten plik wyłącznie układa klasy i zmienne, które komponent rozumie, więc
 * zmiana wyglądu slajdu nie wymaga wchodzenia tutaj, a dołożenie pola w panelu
 * nie wymaga zmiany w motywie.
 *
 * Trzy rzeczy na slajdzie są od siebie niezależne i tak samo są tu zapisane:
 * tło (barwa, gradient, zdjęcie, film), ułożenie treści i sama treść.
 *}

{if $quince_slides|count}
<section class="q-slider{if $quince_slider.WIDTH == 'boxed'} q-slider--boxed{/if}"
         data-q-slider
         data-autoplay="{if $quince_slider.AUTOPLAY}1{else}0{/if}"
         data-interval="{$quince_slider.INTERVAL|intval}"
         style="--q-slider-height: var(--q-slider-height-{$quince_slider.HEIGHT|escape:'html':'UTF-8'})"
         aria-label="{l s='Highlights' d='Modules.Quinceslider.Shop'}">

    <div class="q-slider__track" data-q-slider-track>
        {foreach from=$quince_slides item=slide name=slajdy}
            {* Klasy odmian składane po kolei: tło, przyciemnienie, jasny tekst,
               panel, wyśrodkowanie, wtopienie krawędzi zdjęcia. *}
            {assign var=klasy value="q-slider__slide q-slider__slide--`$slide.background`"}
            {if $slide.shade}{assign var=klasy value="`$klasy` q-slider__slide--shade"}{/if}
            {if $slide.light}{assign var=klasy value="`$klasy` q-slider__slide--light"}{/if}
            {if $slide.layout == 'split'}{assign var=klasy value="`$klasy` q-slider__slide--split"}{/if}
            {if $slide.align == 'center'}{assign var=klasy value="`$klasy` q-slider__slide--center"}{/if}
            {if $slide.background == 'photo' && $slide.fade != 'none'}
                {assign var=klasy value="`$klasy` q-slider__slide--fade-`$slide.fade`"}
            {/if}

            {* Zmienne slajdu. Barwa z palety przychodzi jako nazwa stopnia
               (`neutral-12`), więc zamieniamy ją na odwołanie do zmiennej;
               kod szesnastkowy wchodzi wprost. *}
            {capture name=zmienne}
                {if isset($slide.bg) && $slide.bg}--q-slide-bg: {if $slide.bg|substr:0:1 == '#'}{$slide.bg|escape:'html':'UTF-8'}{else}var(--q-{$slide.bg|escape:'html':'UTF-8'}){/if};{/if}
                {if $slide.background == 'gradient'}
                    {if $slide.from}--q-slide-from: {if $slide.from|substr:0:1 == '#'}{$slide.from|escape:'html':'UTF-8'}{else}var(--q-{$slide.from|escape:'html':'UTF-8'}){/if};{/if}
                    {if $slide.to}--q-slide-to: {if $slide.to|substr:0:1 == '#'}{$slide.to|escape:'html':'UTF-8'}{else}var(--q-{$slide.to|escape:'html':'UTF-8'}){/if};{/if}
                    --q-slide-angle: {$slide.angle|intval}deg;
                {/if}
                {if $slide.background == 'photo' && $slide.image}--q-slide-image: url('{$slide.image|escape:'html':'UTF-8'}');{/if}
                {if $slide.background == 'video' && $slide.poster}--q-slide-poster: url('{$slide.poster|escape:'html':'UTF-8'}');{/if}
                {if $slide.media && $slide.media_share}--q-slide-media-share: {$slide.media_share|intval}%;{/if}
            {/capture}

            <article class="{$klasy}" style="{$smarty.capture.zmienne|strip|escape:'html':'UTF-8'}">

                {* Film jest tłem, nie odtwarzaczem: bez sterowania, wyciszony,
                   w pętli. Bez poprawnego adresu slajd zostaje samą planszą. *}
                {if $slide.background == 'video' && $slide.embed}
                    <iframe class="q-slider__video" src="{$slide.embed|escape:'html':'UTF-8'}"
                            title="{$slide.title|escape:'html':'UTF-8'}" loading="lazy"
                            frameborder="0" allow="autoplay; encrypted-media" tabindex="-1"></iframe>
                {/if}

                {* Licznik stoi bezpośrednio w slajdzie, przed treścią: na szerokim
                   ekranie w rogu slajdu, na wąskim nad treścią. Odlicza go skrypt
                   motywu `countdown.js` (ten sam co przy promocjach produktu) i chowa
                   po terminie. Termin, który już minął, nie trafia na stronę wcale. *}
                {if $slide.timer_to && ($slide.timer_to|qstamp) > $smarty.now}
                    <div class="q-slider__timer" data-q-countdown="{$slide.timer_to|qstamp}">
                        <span class="q-slider__timer-label">{l s='Ends in' d='Modules.Quinceslider.Shop'}</span>
                        <div class="q-slider__timer-parts">
                            <span class="q-slider__timer-part"><span class="q-slider__timer-num" data-q-countdown-days>0</span><span class="q-slider__timer-unit">{l s='days' d='Shop.Theme.Global'}</span></span>
                            <span class="q-slider__timer-part"><span class="q-slider__timer-num" data-q-countdown-hours>00</span><span class="q-slider__timer-unit">{l s='hrs' d='Shop.Theme.Global'}</span></span>
                            <span class="q-slider__timer-part"><span class="q-slider__timer-num" data-q-countdown-minutes>00</span><span class="q-slider__timer-unit">{l s='min' d='Shop.Theme.Global'}</span></span>
                        </div>
                    </div>
                {/if}

                {if $slide.layout == 'split'}<div class="q-slider__panel">{/if}

                <div class="q-slider__inner">
                    <div class="q-slider__layout{if $slide.media} q-slider__layout--with-media{/if}">

                        {if $slide.media}
                            {* Zdjęcie obok treści niesie przekaz razem z nią, więc nie jest
                               tłem — zostaje osobnym obrazem z pustym opisem, bo nagłówek
                               slajdu mówi to samo. *}
                            <div class="q-slider__media">
                                <img src="{$slide.media|escape:'html':'UTF-8'}" alt="" loading="lazy">
                            </div>
                        {/if}

                        <div class="q-slider__body">
                            {if $slide.over}<p class="q-slider__over">{$slide.over|escape:'html':'UTF-8'}</p>{/if}
                            {if $slide.title}<h2 class="q-slider__title">{$slide.title|escape:'html':'UTF-8'}</h2>{/if}
                            {if $slide.text}<p class="q-slider__text">{$slide.text|escape:'html':'UTF-8'}</p>{/if}

                            {if $slide.specs|count}
                                <ul class="q-slider__specs">
                                    {foreach from=$slide.specs item=spec}
                                        <li class="q-slider__spec">{$spec|escape:'html':'UTF-8'}</li>
                                    {/foreach}
                                </ul>
                            {/if}

                            {if $slide.price_now}
                                <p class="q-slider__price">
                                    {if $slide.price_was}<span class="q-slider__price-was">{$slide.price_was|escape:'html':'UTF-8'}</span>{/if}
                                    <span class="q-slider__price-now">{$slide.price_now|escape:'html':'UTF-8'}</span>
                                </p>
                            {/if}

                            {if $slide.buttons|count}
                                <div class="q-slider__actions">
                                    {foreach from=$slide.buttons item=button}
                                        <a class="q-btn q-btn--{$button.style|escape:'html':'UTF-8'} q-btn--lg"
                                           href="{$button.url|escape:'html':'UTF-8'}">{$button.label|escape:'html':'UTF-8'}</a>
                                    {/foreach}
                                </div>
                            {/if}
                        </div>
                    </div>
                </div>

                {if $slide.layout == 'split'}</div>{/if}
            </article>
        {/foreach}
    </div>

    {* Strzałki i kropki w jednym rzędzie przy dolnej krawędzi. Pas z jednym
       slajdem nie dostaje ich wcale: nie ma między czym przechodzić. *}
    {if $quince_slides|count > 1 && ($quince_slider.ARROWS || $quince_slider.DOTS)}
        <div class="q-slider__controls">
            {if $quince_slider.ARROWS}
                <button type="button" class="q-slider__arrow" data-q-slider-prev
                        aria-label="{l s='Previous slide' d='Modules.Quinceslider.Shop'}">
                    <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m10 3-5 5 5 5"/>
                    </svg>
                </button>
            {/if}

            {if $quince_slider.DOTS}
                <div class="q-slider__dots" data-q-slider-dots>
                    {foreach from=$quince_slides item=slide name=kropki}
                        <button type="button" class="q-slider__dot" data-q-slider-dot="{$smarty.foreach.kropki.index}"
                                aria-label="{l s='Slide' d='Modules.Quinceslider.Shop'} {$smarty.foreach.kropki.iteration}"></button>
                    {/foreach}
                </div>
            {/if}

            {if $quince_slider.ARROWS}
                <button type="button" class="q-slider__arrow" data-q-slider-next
                        aria-label="{l s='Next slide' d='Modules.Quinceslider.Shop'}">
                    <svg viewBox="0 0 16 16" width="16" height="16" fill="none" stroke="currentColor"
                         stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m6 3 5 5-5 5"/>
                    </svg>
                </button>
            {/if}
        </div>
    {/if}
</section>
{/if}
