/**
 * Mindoro address dropdowns (Province -> Town/City -> Barangay), reading from
 * the local window.CARESYNC_ADDRESS data (public/assets/js/data/
 * mindoro-address.js): Oriental and Occidental Mindoro, 590 barangays, all
 * filtering/lookup is instant (no network request).
 *
 * Usage: window.CareSyncPsgc.init()
 * Expects the same fixed field ids _step_applicant.php renders:
 *   province_select (a <select>; its value is the province code),
 *   city_search, address_city, city_municipality_code,
 *   barangay_search, address_barangay, barangay_code,
 * each city/barangay input's own .psgc-dropdown/.psgc-error living inside
 * its .psgc-field wrapper (same markup _step_applicant.php renders).
 * No-ops (returns null) if the page has no #city_search - not every page
 * using caresync-wizard.js necessarily has the address step wired in yet.
 */
window.CareSyncPsgc = (function () {
    function init() {
        const citySearch = document.getElementById('city_search');
        if (!citySearch) {
            return null;
        }

        const provinceSelect = document.getElementById('province_select');
        const addressCityInput = document.getElementById('address_city');
        const cityCodeInput = document.getElementById('city_municipality_code');
        const cityDropdown = citySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
        const cityError = document.querySelector('.psgc-error[data-field="city"]');

        const barangaySearch = document.getElementById('barangay_search');
        const addressBarangayInput = document.getElementById('address_barangay');
        const barangayCodeInput = document.getElementById('barangay_code');
        const barangayDropdown = barangaySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
        const barangayError = document.querySelector('.psgc-error[data-field="barangay"]');

        // Every barangay with its town/city, so the Barangay box can be searched
        // on its own (before any Town/City is chosen).
        let barangayOptions = [];
        let lastSelectedCityName = addressCityInput.value || '';
        let lastSelectedBarangayName = addressBarangayInput.value || '';

        function showDropdown(el) { el.classList.remove('d-none'); }
        function hideDropdown(el) { el.classList.add('d-none'); el.innerHTML = ''; }

        function renderEmpty(el, text) {
            el.innerHTML = '';
            const empty = document.createElement('div');
            empty.className = 'psgc-empty';
            empty.textContent = text;
            el.appendChild(empty);
            showDropdown(el);
        }

        function renderResults(el, results, onPick) {
            el.innerHTML = '';
            results.forEach(function (rowData) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action py-2';
                item.textContent = rowData.label || rowData.name;
                item.addEventListener('click', function () {
                    onPick(rowData);
                });
                el.appendChild(item);
            });
            showDropdown(el);
        }

        function clearFieldError(el) {
            el.classList.add('d-none');
            el.textContent = '';
        }

        function showFieldError(el, text) {
            el.textContent = text;
            el.classList.remove('d-none');
        }

        // The data file's own city.code is always 10-digit (matches what's
        // already saved in plan_holders.city_municipality_code/barangay_code -
        // checked directly against the live table before choosing this), so
        // every hidden field gets set to .code even when a match came in via
        // .old_code on preselect (a saved 9-digit legacy code, if any ever
        // existed, normalizes to 10-digit going forward).
        const address = window.CARESYNC_ADDRESS;
        if (!address || !Array.isArray(address.provinces)) {
            console.error('CareSyncPsgc: window.CARESYNC_ADDRESS is missing or malformed.');
            showFieldError(cityError, 'Address list failed to load - refresh the page.');
            showFieldError(barangayError, 'Address list failed to load - refresh the page.');
            citySearch.disabled = true;
            barangaySearch.disabled = true;
            return { selectCity: function () {}, selectBarangay: function () {} };
        }

        // "City of Calapan" should match a search for "Calapan" - strip a
        // leading "City of " before comparing, both for the stored name and
        // whatever the user typed.
        function normalize(name) {
            return name.toLowerCase().replace(/^city of\s+/, '').trim();
        }

        // code/old_code -> city (with its own .barangays) and -> {code, name, cityCode}, across BOTH provinces:
        // a saved address (edit page) may be in either, and either code format.
        const citiesByCode = {};
        const barangaysByCode = {};
        const provinceOfCity = {};
        address.provinces.forEach(function (province) {
            province.cities.forEach(function (city) {
                citiesByCode[city.code] = city;
                citiesByCode[city.old_code] = city;
                provinceOfCity[city.code] = province.code;
                provinceOfCity[city.old_code] = province.code;
                city.barangays.forEach(function (barangay) {
                    const row = { code: barangay.code, name: barangay.name, cityCode: city.code };
                    barangaysByCode[barangay.code] = row;
                    barangaysByCode[barangay.old_code] = row;
                });
            });
        });

        // Only the chosen province's towns/cities and barangays are offered.
        let cities = [];
        let allBarangays = [];
        function useProvince(code) {
            const province = address.provinces.find(function (p) { return p.code === code; });
            cities = province ? province.cities.map(function (c) { return { code: c.code, name: c.name }; }) : [];
            allBarangays = [];
            if (province) {
                province.cities.forEach(function (city) {
                    city.barangays.forEach(function (barangay) {
                        allBarangays.push({ code: barangay.code, name: barangay.name, cityCode: city.code, label: barangay.name + ', ' + city.name });
                    });
                });
            }
        }
        useProvince(provinceSelect ? provinceSelect.value : address.provinces[0].code);
        function searchCities(query) {
            const needle = normalize(query);
            const results = needle === ''
                ? cities
                : cities.filter(function (c) { return normalize(c.name).indexOf(needle) !== -1; });

            if (results.length === 0) {
                renderEmpty(cityDropdown, provinceSelect && !provinceSelect.value ? 'Choose a province first.' : 'No matching town/city found.');
                return;
            }
            renderResults(cityDropdown, results, selectCity);
        }

        function selectCity(rowData) {
            citySearch.value = rowData.name;
            addressCityInput.value = rowData.name;
            cityCodeInput.value = rowData.code;
            lastSelectedCityName = rowData.name;
            clearFieldError(cityError);
            hideDropdown(cityDropdown);
            resetBarangay();
            loadBarangays(rowData.code);
        }

        function resetBarangay() {
            barangaySearch.value = '';
            barangaySearch.placeholder = 'Search barangay...';
            addressBarangayInput.value = '';
            barangayCodeInput.value = '';
            lastSelectedBarangayName = '';
            barangayOptions = [];
            hideDropdown(barangayDropdown);
            clearFieldError(barangayError);
        }

        citySearch.addEventListener('input', function () {
            if (citySearch.value !== lastSelectedCityName) {
                addressCityInput.value = '';
                cityCodeInput.value = '';
                // No town/city chosen any more: the Barangay box searches everywhere again.
                barangayOptions = [];
            }
            searchCities(citySearch.value.trim());
        });

        citySearch.addEventListener('focus', function () {
            searchCities(citySearch.value.trim());
        });

        function loadBarangays(cityCode, preselectCode) {
            const city = citiesByCode[cityCode];
            barangayOptions = city
                ? city.barangays.map(function (b) { return { code: b.code, name: b.name }; })
                : [];
            barangaySearch.placeholder = 'Search barangay...';
            hideDropdown(barangayDropdown);

            if (preselectCode) {
                const match = barangayOptions.find(function (b) { return b.code === preselectCode; })
                    || (barangaysByCode[preselectCode] && barangaysByCode[preselectCode].cityCode === cityCode
                        ? barangaysByCode[preselectCode]
                        : null);
                if (match) {
                    selectBarangay(match);
                }
            }
        }

        function filterBarangays(query) {
            const needle = query.trim().toLowerCase();
            // With a Town/City chosen, search its barangays; otherwise search all of them.
            const pool = barangayOptions.length > 0 ? barangayOptions : allBarangays;
            const results = needle === ''
                ? (barangayOptions.length > 0 ? pool : [])
                : pool.filter(function (b) { return b.name.toLowerCase().indexOf(needle) !== -1; }).slice(0, 40);

            if (needle === '' && results.length === 0) {
                renderEmpty(barangayDropdown, 'Type a barangay name to search all towns/cities.');
                return;
            }

            if (results.length === 0) {
                renderEmpty(barangayDropdown, 'No matching barangay found.');
                return;
            }
            renderResults(barangayDropdown, results, selectBarangay);
        }

        function selectBarangay(rowData) {
            // Picked without a Town/City (or from another one): fill the Town/City in too.
            if (rowData.cityCode && cityCodeInput.value !== rowData.cityCode && citiesByCode[rowData.cityCode]) {
                const city = citiesByCode[rowData.cityCode];
                citySearch.value = city.name;
                addressCityInput.value = city.name;
                cityCodeInput.value = city.code;
                lastSelectedCityName = city.name;
                clearFieldError(cityError);
                barangayOptions = city.barangays.map(function (b) { return { code: b.code, name: b.name }; });
            }
            barangaySearch.value = rowData.name;
            addressBarangayInput.value = rowData.name;
            barangayCodeInput.value = rowData.code;
            lastSelectedBarangayName = rowData.name;
            clearFieldError(barangayError);
            hideDropdown(barangayDropdown);
        }

        barangaySearch.addEventListener('input', function () {
            if (barangaySearch.value !== lastSelectedBarangayName) {
                addressBarangayInput.value = '';
                barangayCodeInput.value = '';
            }
            filterBarangays(barangaySearch.value);
        });

        barangaySearch.addEventListener('focus', function () {
            filterBarangays(barangaySearch.value);
        });

        document.addEventListener('click', function (event) {
            if (!citySearch.closest('.psgc-field').contains(event.target)) {
                hideDropdown(cityDropdown);
            }
            if (!barangaySearch.closest('.psgc-field').contains(event.target)) {
                hideDropdown(barangayDropdown);
            }
        });

        function resetCity() {
            citySearch.value = '';
            addressCityInput.value = '';
            cityCodeInput.value = '';
            lastSelectedCityName = '';
            hideDropdown(cityDropdown);
            clearFieldError(cityError);
        }

        // Changing the province clears the Town/City and Barangay chosen under the old one.
        if (provinceSelect) {
            provinceSelect.addEventListener('change', function () {
                useProvince(provinceSelect.value);
                resetCity();
                resetBarangay();
            });
        }

        // A new registration starts in the province of the chosen branch (until an address has been picked).
        const branchSelect = document.getElementById('branch_id');
        if (provinceSelect && branchSelect && branchSelect.tagName === 'SELECT') {
            branchSelect.addEventListener('change', function () {
                const option = branchSelect.options[branchSelect.selectedIndex];
                const code = option ? option.getAttribute('data-province') : '';
                if (code && !cityCodeInput.value.trim() && provinceSelect.value !== code) {
                    provinceSelect.value = code;
                    provinceSelect.dispatchEvent(new Event('change'));
                }
            });
        }

        const savedCityCode = cityCodeInput.value.trim();
        const savedBarangayCode = barangayCodeInput.value.trim();
        if (savedCityCode !== '') {
            const savedCity = citiesByCode[savedCityCode];
            if (savedCity) {
                // The saved address decides the province (it may not be the branch's).
                if (provinceSelect && provinceOfCity[savedCityCode]) {
                    provinceSelect.value = provinceOfCity[savedCityCode];
                    useProvince(provinceSelect.value);
                }
                // Normalize a legacy 9-digit old_code to the canonical
                // 10-digit code, same as a fresh selection would.
                cityCodeInput.value = savedCity.code;
                barangaySearch.value = lastSelectedBarangayName;
                loadBarangays(savedCity.code, savedBarangayCode || null);
            }
        }

        return { selectCity: selectCity, selectBarangay: selectBarangay };
    }

    return { init: init };
})();
