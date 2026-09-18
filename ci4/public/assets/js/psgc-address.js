/**
 * PSGC Cloud address dropdowns (Town/City -> Barangay), extracted verbatim
 * (Prompt 7, Step A) from client/plan_registration.php's own inline
 * <script> so every registration page built on partials/registration/*
 * gets the identical search-as-you-type city/barangay picker instead of
 * staff-side forms' old free-text address boxes.
 *
 * Usage: window.CareSyncPsgc.init({ addressApiBase: '<?= base_url('api/address') ?>' })
 * Expects the same fixed field ids _step_applicant.php renders:
 *   city_search, address_city, city_municipality_code,
 *   barangay_search, address_barangay, barangay_code,
 * each city/barangay input's own .psgc-dropdown/.psgc-error living inside
 * its .psgc-field wrapper (same markup _step_applicant.php renders).
 * No-ops (returns null) if the page has no #city_search - not every page
 * using caresync-wizard.js necessarily has the address step wired in yet.
 */
window.CareSyncPsgc = (function () {
    function init(config) {
        const citySearch = document.getElementById('city_search');
        if (!citySearch) {
            return null;
        }

        const ADDRESS_API_BASE = config.addressApiBase;

        const addressCityInput = document.getElementById('address_city');
        const cityCodeInput = document.getElementById('city_municipality_code');
        const cityDropdown = citySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
        const cityError = document.querySelector('.psgc-error[data-field="city"]');

        const barangaySearch = document.getElementById('barangay_search');
        const addressBarangayInput = document.getElementById('address_barangay');
        const barangayCodeInput = document.getElementById('barangay_code');
        const barangayDropdown = barangaySearch.closest('.psgc-field').querySelector('.psgc-dropdown');
        const barangayError = document.querySelector('.psgc-error[data-field="barangay"]');

        let cityDebounce = null;
        let citySearchToken = 0;
        let barangayOptions = [];
        let lastSelectedCityName = addressCityInput.value || '';
        let lastSelectedBarangayName = addressBarangayInput.value || '';

        function showDropdown(el) { el.classList.remove('d-none'); }
        function hideDropdown(el) { el.classList.add('d-none'); el.innerHTML = ''; }

        function renderMessage(el, text, isError, retryFn) {
            el.innerHTML = '';
            const rowEl = document.createElement('div');
            rowEl.className = isError ? 'psgc-retry' : 'psgc-loading';
            rowEl.textContent = text + ' ';
            if (isError && retryFn) {
                const retryBtn = document.createElement('button');
                retryBtn.type = 'button';
                retryBtn.className = 'btn btn-link btn-sm p-0 align-baseline';
                retryBtn.textContent = 'Retry';
                retryBtn.addEventListener('click', retryFn);
                rowEl.appendChild(retryBtn);
            }
            el.appendChild(rowEl);
            showDropdown(el);
        }

        function clearFieldError(el) {
            el.classList.add('d-none');
            el.textContent = '';
        }

        function searchCities(query) {
            const token = ++citySearchToken;
            renderMessage(cityDropdown, 'Loading towns/cities...', false);

            fetch(ADDRESS_API_BASE + '/cities?q=' + encodeURIComponent(query))
                .then((res) => {
                    if (!res.ok) { throw new Error('bad_status'); }
                    return res.json();
                })
                .then((payload) => {
                    if (token !== citySearchToken) { return; }
                    const results = Array.isArray(payload.data) ? payload.data : [];
                    renderCityResults(results);
                })
                .catch(() => {
                    if (token !== citySearchToken) { return; }
                    renderMessage(cityDropdown, 'Unable to load Philippine address data. Please try again.', true, function () {
                        searchCities(query);
                    });
                });
        }

        function renderCityResults(results) {
            cityDropdown.innerHTML = '';
            if (results.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'psgc-empty';
                empty.textContent = 'No matching town/city found.';
                cityDropdown.appendChild(empty);
                showDropdown(cityDropdown);
                return;
            }

            results.forEach(function (rowData) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action py-2';
                item.textContent = rowData.name;
                item.addEventListener('click', function () {
                    selectCity(rowData);
                });
                cityDropdown.appendChild(item);
            });
            showDropdown(cityDropdown);
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
            barangaySearch.placeholder = 'Loading barangays...';
            barangaySearch.disabled = true;
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
            }

            const query = citySearch.value.trim();
            window.clearTimeout(cityDebounce);

            if (query.length < 2) {
                hideDropdown(cityDropdown);
                return;
            }

            cityDebounce = window.setTimeout(function () {
                searchCities(query);
            }, 300);
        });

        citySearch.addEventListener('focus', function () {
            if (citySearch.value.trim().length >= 2) {
                searchCities(citySearch.value.trim());
            }
        });

        function loadBarangays(cityCode, preselectCode) {
            renderMessage(barangayDropdown, 'Loading barangays...', false);

            fetch(ADDRESS_API_BASE + '/barangays/' + encodeURIComponent(cityCode))
                .then((res) => {
                    if (!res.ok) { throw new Error('bad_status'); }
                    return res.json();
                })
                .then((payload) => {
                    barangayOptions = Array.isArray(payload.data) ? payload.data : [];
                    barangaySearch.disabled = false;
                    barangaySearch.placeholder = 'Search barangay...';
                    hideDropdown(barangayDropdown);

                    if (preselectCode) {
                        const match = barangayOptions.find(function (b) { return b.code === preselectCode; });
                        if (match) {
                            selectBarangay(match);
                        }
                    }
                })
                .catch(() => {
                    barangaySearch.placeholder = 'Unable to load barangays';
                    renderMessage(barangayDropdown, 'Unable to load Philippine address data. Please try again.', true, function () {
                        loadBarangays(cityCode, preselectCode);
                    });
                });
        }

        function filterBarangays(query) {
            const needle = query.trim().toLowerCase();
            const results = needle === ''
                ? barangayOptions
                : barangayOptions.filter(function (b) { return b.name.toLowerCase().indexOf(needle) !== -1; });

            barangayDropdown.innerHTML = '';
            if (results.length === 0) {
                const empty = document.createElement('div');
                empty.className = 'psgc-empty';
                empty.textContent = 'No matching barangay found.';
                barangayDropdown.appendChild(empty);
                showDropdown(barangayDropdown);
                return;
            }

            results.forEach(function (rowData) {
                const item = document.createElement('button');
                item.type = 'button';
                item.className = 'list-group-item list-group-item-action py-2';
                item.textContent = rowData.name;
                item.addEventListener('click', function () {
                    selectBarangay(rowData);
                });
                barangayDropdown.appendChild(item);
            });
            showDropdown(barangayDropdown);
        }

        function selectBarangay(rowData) {
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
            if (!barangaySearch.disabled) {
                filterBarangays(barangaySearch.value);
            }
        });

        document.addEventListener('click', function (event) {
            if (!citySearch.closest('.psgc-field').contains(event.target)) {
                hideDropdown(cityDropdown);
            }
            if (!barangaySearch.closest('.psgc-field').contains(event.target)) {
                hideDropdown(barangayDropdown);
            }
        });

        const savedCityCode = cityCodeInput.value.trim();
        const savedBarangayCode = barangayCodeInput.value.trim();
        if (savedCityCode !== '') {
            barangaySearch.value = lastSelectedBarangayName;
            loadBarangays(savedCityCode, savedBarangayCode || null);
        } else {
            barangaySearch.placeholder = 'Select a Town/City first';
        }

        return { selectCity: selectCity, selectBarangay: selectBarangay };
    }

    return { init: init };
})();
