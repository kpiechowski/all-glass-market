{{--
    Font switcher (D-32, D-34): loads the candidates from Bunny Fonts, the provider Filament uses,
    and swaps Filament's `--font-family` variable on <html>. Only this page; removed once a font is chosen.
--}}
<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=roboto:400,500,600,700|inter:400,500,600,700|manrope:400,500,600,700&display=swap" rel="stylesheet" />

<div
    wire:ignore
    x-data="{
        storageKey: 'styleguide:font',
        font: '',
        init() {
            let saved = null;
            try { saved = window.localStorage.getItem(this.storageKey); } catch (e) {}
            this.apply(saved ?? '');
        },
        apply(font) {
            this.font = font;
            if (font === '') {
                document.documentElement.style.removeProperty('--font-family');
            } else {
                document.documentElement.style.setProperty('--font-family', `'${font}'`);
            }
            try { window.localStorage.setItem(this.storageKey, font); } catch (e) {}
        },
    }"
    style="display: flex; align-items: center; justify-content: flex-end; gap: 0.75rem;"
>
    <label for="styleguide-font" style="font-size: 0.875rem; font-weight: 500;">Krój pisma</label>
    <x-filament::input.wrapper style="width: 14rem;">
        <x-filament::input.select id="styleguide-font" x-model="font" x-on:change="apply($event.target.value)">
            <option value="">Poppins (obecny)</option>
            <option value="Roboto">Roboto</option>
            <option value="Inter">Inter</option>
            <option value="Manrope">Manrope</option>
        </x-filament::input.select>
    </x-filament::input.wrapper>
</div>
