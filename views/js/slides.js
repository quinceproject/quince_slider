/**
 * Ekran „Slajder": to, czego nie obsługuje sam znacznik.
 *
 * Slajdy stoją w stronie jako gotowy znacznik z Twiga, a kolejność zmienia
 * Alpine (`x-sort`). Skrypt robi cztery rzeczy:
 *
 *   1. rozwija i zwija wiersz slajdu,
 *   2. dokłada slajd, pigułkę i przycisk z wzoru w `<template>` oraz je kasuje,
 *   3. pokazuje pola należące do wybranego rodzaju tła i gasi pozostałe,
 *   4. spisuje całość do pola `SLIDES` przy wysyłce.
 *
 * Żadna lista wartości nie jest tu przepisana: etykiety skrypt czyta z opcji,
 * które wstawił Twig.
 */
(function () {
    'use strict';

    var formularz = document.getElementById('quince-slider');

    if (!formularz) {
        return;
    }

    var lista = formularz.querySelector('[data-slajdy]');
    var wzor = formularz.querySelector('[data-wzor-slajdu]');
    var poleZapisu = formularz.querySelector('[name="SLIDES"]');

    var jezyki = JSON.parse(formularz.dataset.languages || '[]');
    var napisy = JSON.parse(formularz.dataset.napisy || '{}');
    var maxPigulek = parseInt(formularz.dataset.maxSpecs, 10) || 4;
    var maxPrzyciskow = parseInt(formularz.dataset.maxButtons, 10) || 2;

    // ── drobiazgi ────────────────────────────────────────────────────────

    function pole(gdzie, nazwa) {
        return gdzie.querySelector('[data-pole="' + nazwa + '"]');
    }

    function segment(pojemnik) {
        var wybrany = pojemnik ? pojemnik.querySelector('.qc-seg-btn.is-active') : null;

        return wybrany ? wybrany.dataset.wartosc : '';
    }

    function zaznaczony(pojemnik) {
        var wejscie = pojemnik ? pojemnik.querySelector('input[type="checkbox"]') : null;

        return wejscie ? wejscie.checked : false;
    }

    /** Napisy z komponentu wielojęzycznego, sparowane po kolejności języków. */
    function napisyPola(pojemnik) {
        var wynik = {};

        if (!pojemnik) {
            return wynik;
        }

        pojemnik.querySelectorAll('.qc-multilang-row input[type="text"]').forEach(function (wejscie, i) {
            var tekst = wejscie.value.trim();

            if (tekst !== '' && jezyki[i] !== undefined) {
                wynik[jezyki[i]] = tekst;
            }
        });

        return wynik;
    }

    /** Pierwszy wypełniony napis: do opisu w pasku slajdu. */
    function pierwszyNapis(pojemnik) {
        var napisyMapy = napisyPola(pojemnik);
        var klucze = Object.keys(napisyMapy);

        return klucze.length ? napisyMapy[klucze[0]] : '';
    }

    // ── odświeżanie slajdu ───────────────────────────────────────────────

    function odswiezSlajd(slajd) {
        if (!slajd) {
            return;
        }

        var tlo = segment(pole(slajd, 'background'));

        /* Pola należą do rodzaju tła: slajd na barwie nie ma po co pytać
           o adres filmu. Jeden blok bywa wspólny dla dwóch rodzajów, stąd
           lista nazw zamiast jednej. */
        slajd.querySelectorAll('[data-gdy-tlo]').forEach(function (blok) {
            blok.hidden = blok.dataset.gdyTlo.split(' ').indexOf(tlo) === -1;
        });

        /* Udział zdjęcia ma sens dopiero wtedy, gdy zdjęcie jest wskazane. */
        /* Slajd o produkcie bierze nazwe, cene i zdjecie z katalogu, wiec
           pola tresci staja sie nadpisaniem, a nie obowiazkiem. */
        var zrodlo = segment(pole(slajd, 'source'));

        slajd.querySelectorAll('[data-gdy-zrodlo]').forEach(function (blok) {
            blok.hidden = blok.dataset.gdyZrodlo !== zrodlo;
        });

        /* Pola, które przy produkcie przychodzą z katalogu, znikają z ekranu.
           Wracają dopiero po włączeniu nadpisania. */
        var nadpisuje = zrodlo === 'text' || zaznaczony(pole(slajd, 'override'));

        slajd.querySelectorAll('[data-z-katalogu]').forEach(function (blok) {
            blok.hidden = !nadpisuje;
        });

        var media = pole(slajd, 'media').value.trim();
        var udzial = slajd.querySelector('[data-gdy-media]');

        if (udzial) {
            udzial.hidden = media === '';
        }

        odswiezListe(slajd, 'pigulki', maxPigulek, '[data-pigulka]');
        odswiezListe(slajd, 'przyciski', maxPrzyciskow, '[data-przycisk]');

        opiszSlajd(slajd, tlo, media);
    }

    /** Licznik i wyłącznik dokładania przy liście pigułek albo przycisków. */
    function odswiezListe(slajd, nazwa, sufit, selektor) {
        var ile = slajd.querySelectorAll(selektor).length;
        var licznik = slajd.querySelector('[data-' + nazwa + '-licznik]');
        var dodaj = slajd.querySelector('[data-dodaj-' + (nazwa === 'pigulki' ? 'pigulke' : 'przycisk') + ']');

        if (licznik) {
            licznik.textContent = ile + ' / ' + sufit;
        }

        if (dodaj) {
            dodaj.disabled = ile >= sufit;
        }
    }

    /** Jedno zdanie w pasku: co ten slajd pokazuje. */
    function opiszSlajd(slajd, tlo, media) {
        var czesci = [napisy[tlo] || tlo];

        if (segment(pole(slajd, 'layout')) === 'split') { czesci.push(napisy.panel); }
        if (segment(pole(slajd, 'align')) === 'center') { czesci.push(napisy.centred); }
        if (media !== '') { czesci.push(napisy.withMedia); }

        var pigulek = slajd.querySelectorAll('[data-pigulka]').length;
        var przyciskow = slajd.querySelectorAll('[data-przycisk]').length;

        if (pigulek) { czesci.push(pigulek + ' ' + napisy.specs); }
        if (przyciskow) { czesci.push(przyciskow + ' ' + napisy.buttons); }

        var tytul = pierwszyNapis(pole(slajd, 'title'));

        slajd.querySelector('[data-nazwa]').textContent = tytul !== ''
            ? tytul
            : napisy.slide + ' ' + (numerSlajdu(slajd) + 1);

        slajd.querySelector('[data-opis]').textContent = czesci.join(' · ');
    }

    function numerSlajdu(slajd) {
        return Array.prototype.indexOf.call(formularz.querySelectorAll('[data-slajd]'), slajd);
    }

    function odswiezWszystko() {
        var slajdy = formularz.querySelectorAll('[data-slajd]');

        formularz.querySelector('[data-pusto]').hidden = slajdy.length > 0;

        slajdy.forEach(odswiezSlajd);
    }

    // ── dokładanie i kasowanie ───────────────────────────────────────────

    function zWzoru(gdzie, nazwaWzoru, gdzieWstawic) {
        var szablon = gdzie.querySelector(nazwaWzoru);

        gdzieWstawic.appendChild(szablon.content.firstElementChild.cloneNode(true));
    }

    formularz.addEventListener('click', function (zdarzenie) {
        var cel = zdarzenie.target.closest('button');
        var slajd = zdarzenie.target.closest('[data-slajd]');

        /* Klik w pasek rozwija wiersz. Przełącznik, chwyt i ikonki mają własne
           zadania, więc ich nie dotyczy. */
        if (!cel && zdarzenie.target.closest('[data-pasek]') && !zdarzenie.target.closest('.qc-toggle, .qc-rep-handle')) {
            rozwin(slajd);

            return;
        }

        if (!cel) {
            return;
        }

        if (cel.classList.contains('qc-seg-btn')) {
            cel.parentElement.querySelectorAll('.qc-seg-btn').forEach(function (przycisk) {
                przycisk.classList.toggle('is-active', przycisk === cel);
            });

            /* Ustawienia całego pasa mają pola ukryte, bo idą zwykłą wysyłką,
               a nie przez JSON. */
            var ukryte = cel.parentElement.parentElement.querySelector('input[type="hidden"]');

            if (ukryte) {
                ukryte.value = cel.dataset.wartosc;
            }

            odswiezSlajd(slajd);

            return;
        }

        if (cel.hasAttribute('data-rozwin')) {
            rozwin(slajd);

            return;
        }

        if (cel.hasAttribute('data-dodaj-slajd')) {
            var nowy = wzor.content.firstElementChild.cloneNode(true);
            var numer = formularz.querySelectorAll('[data-slajd]').length + 1;

            pole(nowy, 'id').value = 'slide-' + numer;
            nowy.setAttribute('x-sort:item', "'slide-" + numer + "'");

            lista.appendChild(nowy);
            rozwin(nowy);
            odswiezWszystko();

            return;
        }

        if (cel.hasAttribute('data-usun-slajd')) {
            slajd.remove();
            odswiezWszystko();

            return;
        }

        if (cel.hasAttribute('data-dodaj-pigulke')) {
            zWzoru(slajd, '[data-wzor-pigulki]', slajd.querySelector('[data-pigulki]'));
            odswiezSlajd(slajd);

            return;
        }

        if (cel.hasAttribute('data-dodaj-przycisk')) {
            zWzoru(slajd, '[data-wzor-przycisku]', slajd.querySelector('[data-przyciski]'));
            odswiezSlajd(slajd);

            return;
        }

        if (cel.hasAttribute('data-usun-pigulke')) {
            cel.closest('[data-pigulka]').remove();
            odswiezSlajd(slajd);

            return;
        }

        if (cel.hasAttribute('data-usun-przycisk')) {
            cel.closest('[data-przycisk]').remove();
            odswiezSlajd(slajd);
        }
    });

    function rozwin(slajd) {
        /* Ciało slajdu to pierwsze `qc-rep-body` W NIM, a nie w jego pigułkach:
           tamte stoją na tym samym komponencie. */
        var body = slajd.querySelector(':scope > .qc-rep-body');

        body.hidden = !body.hidden;
        slajd.classList.toggle('qc-rep-row--otwarty', !body.hidden);
    }

    formularz.addEventListener('change', function (zdarzenie) {
        odswiezSlajd(zdarzenie.target.closest('[data-slajd]'));
    });

    formularz.addEventListener('input', function (zdarzenie) {
        odswiezSlajd(zdarzenie.target.closest('[data-slajd]'));
    });

    /* Przeciągnięcie zmienia kolejność w drzewie strony, a nie kliknięciem. */
    if (lista) {
        new MutationObserver(odswiezWszystko).observe(lista, { childList: true });
    }

    // ── spis do pola formularza ──────────────────────────────────────────

    formularz.addEventListener('submit', function () {
        var slajdy = [];

        formularz.querySelectorAll('[data-slajd]').forEach(function (slajd) {
            var tlo = segment(pole(slajd, 'background'));

            /* Przy slajdzie o produkcie bez nadpisania pola treści idą puste:
               to, co widać na ekranie, ma być tym, co trafia do zapisu. */
            var zKatalogu = segment(pole(slajd, 'source')) === 'product'
                && !zaznaczony(pole(slajd, 'override'));

            var wpis = {
                id: pole(slajd, 'id').value,
                active: zaznaczony(pole(slajd, 'active')),
                background: tlo,

                source: segment(pole(slajd, 'source')),
                product: parseInt(pole(slajd, 'product').value, 10) || 0,

                layout: segment(pole(slajd, 'layout')),
                align: segment(pole(slajd, 'align')),
                light: zaznaczony(pole(slajd, 'light')),
                shade: zaznaczony(pole(slajd, 'shade')),

                over: napisyPola(pole(slajd, 'over')),
                title: zKatalogu ? {} : napisyPola(pole(slajd, 'title')),
                text: zKatalogu ? {} : napisyPola(pole(slajd, 'text')),

                price_now: zKatalogu ? '' : pole(slajd, 'price_now').value.trim(),
                price_was: zKatalogu ? '' : pole(slajd, 'price_was').value.trim(),
                timer_to: pole(slajd, 'timer_to').value.replace('T', ' '),

                media: zKatalogu ? '' : pole(slajd, 'media').value.trim(),
                media_share: parseInt(pole(slajd, 'media_share').value, 10) || 40,

                specs: [],
                buttons: []
            };

            /* Pola tła: zapisujemy tylko te, które należą do wybranego rodzaju. */
            if (tlo === 'gradient') {
                wpis.from = pole(slajd, 'from').value.trim();
                wpis.to = pole(slajd, 'to').value.trim();
                wpis.angle = parseInt(pole(slajd, 'angle').value, 10) || 0;
            } else if (tlo === 'photo') {
                wpis.image = pole(slajd, 'image').value.trim();
                wpis.fade = pole(slajd, 'fade').value;
                wpis.bg = pole(slajd, 'bg').value.trim();
            } else if (tlo === 'video') {
                wpis.video = pole(slajd, 'video').value.trim();
                wpis.poster = pole(slajd, 'poster').value.trim();
            } else {
                wpis.bg = pole(slajd, 'bg').value.trim();
            }

            slajd.querySelectorAll('[data-pigulka]').forEach(function (pigulka) {
                var napisyPigulki = napisyPola(pole(pigulka, 'spec'));

                if (Object.keys(napisyPigulki).length) {
                    wpis.specs.push(napisyPigulki);
                }
            });

            slajd.querySelectorAll('[data-przycisk]').forEach(function (przycisk) {
                wpis.buttons.push({
                    label: napisyPola(pole(przycisk, 'label')),
                    url: zKatalogu ? '' : pole(przycisk, 'url').value.trim(),
                    style: segment(pole(przycisk, 'style'))
                });
            });

            slajdy.push(wpis);
        });

        poleZapisu.value = JSON.stringify(slajdy);
    });

    odswiezWszystko();
})();
