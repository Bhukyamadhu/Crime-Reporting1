console.log("JS STARTED");

document.addEventListener("DOMContentLoaded", function () {
    console.log("DOM READY");

    try {
        const btn = document.getElementById("nextBtn");
        console.log("Button found:", btn);

        if (!btn) {
            console.error("Button NOT FOUND");
            return;
        }

        let currentStep = 0;
        const form = document.getElementById("complaintWizardForm");
        const steps = document.querySelectorAll(".step");
        const progressBar = document.getElementById("progressBar");
        const nextButtons = document.querySelectorAll(".nextBtn");
        const prevButtons = document.querySelectorAll(".prevBtn");

        if (!form || !steps.length) {
            console.error("Report form steps not found");
            return;
        }

        const hasAddress = form.dataset.hasAddress === "1";
        const existingEvidence = form.dataset.existingEvidence || "";

        const evidenceInput = document.getElementById("evidenceInput");
        const evidenceFileName = document.getElementById("evidenceFileName");
        const reviewEvidence = document.getElementById("reviewEvidence");
        const latitudeInput = document.getElementById("latitude");
        const longitudeInput = document.getElementById("longitude");
        const addressInput = document.getElementById("address");
        const latitudeDisplay = document.getElementById("displayLat");
        const longitudeDisplay = document.getElementById("displayLng");
        const addressDisplay = document.getElementById("displayAddress");
        const searchInput = document.getElementById("locationSearch");
        const searchButton = document.getElementById("searchLocationBtn");
        const searchLoading = document.getElementById("mapSearchLoading");
        const searchFeedback = document.getElementById("mapSearchFeedback");

        let map = null;
        let marker = null;

        function updateProgress() {
            const percent = ((currentStep + 1) / steps.length) * 100;
            if (progressBar) {
                progressBar.style.width = percent + "%";
                progressBar.setAttribute("aria-valuenow", String(percent));
            }
        }

        function updateReviewField(id, value) {
            const node = document.getElementById(id);
            if (node) node.textContent = value || "Not provided";
        }

        function syncReview() {
            const evidenceLabel = evidenceInput && evidenceInput.files && evidenceInput.files[0]
                ? evidenceInput.files[0].name
                : (existingEvidence || "No file selected");

            updateReviewField("reviewFullName", form.querySelector('input[name="full_name"]')?.value.trim());
            updateReviewField("reviewAge", form.querySelector('input[name="age"]')?.value.trim());
            updateReviewField("reviewNationality", form.querySelector('input[name="nationality"]')?.value.trim());
            updateReviewField("reviewPhone", form.querySelector('input[name="phone_number"]')?.value.trim());
            updateReviewField("reviewReporterAddress", form.querySelector('textarea[name="reporter_address"]')?.value.trim());
            updateReviewField("reviewCrimeType", form.querySelector('select[name="crime_type"]')?.value.trim());
            updateReviewField("reviewDescription", form.querySelector('textarea[name="description"]')?.value.trim());
            updateReviewField("reviewLatitude", latitudeInput && latitudeInput.value ? Number(latitudeInput.value).toFixed(6) : "Not selected");
            updateReviewField("reviewLongitude", longitudeInput && longitudeInput.value ? Number(longitudeInput.value).toFixed(6) : "Not selected");
            updateReviewField("reviewIncidentAddress", addressInput?.value.trim() || "Not selected");
            updateReviewField("reviewEvidence", evidenceLabel);
        }

        function showStep(index) {
            steps.forEach((step, i) => {
                step.classList.toggle("active", i === index);
            });
            updateProgress();
            if (index === 2 && map) {
                window.setTimeout(function () {
                    map.invalidateSize();
                }, 120);
            }
            if (index === 3) {
                syncReview();
            }
        }

        document.querySelectorAll(".nextBtn").forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                console.log("NEXT CLICKED");

                if (btn.id === "nextBtn") {
                    console.log("CLICK WORKING");
                    alert("Next button works now");
                }

                if (currentStep < steps.length - 1) {
                    currentStep++;
                    showStep(currentStep);
                }
            });
        });

        document.querySelectorAll(".prevBtn").forEach(btn => {
            btn.addEventListener("click", function (e) {
                e.preventDefault();
                console.log("PREV CLICKED");

                if (currentStep > 0) {
                    currentStep--;
                    showStep(currentStep);
                }
            });
        });

        if (evidenceInput) {
            evidenceInput.addEventListener("change", function () {
                const fileName = evidenceInput.files && evidenceInput.files[0]
                    ? evidenceInput.files[0].name
                    : (existingEvidence || "No new file selected");
                if (evidenceFileName) evidenceFileName.textContent = fileName;
                if (reviewEvidence) reviewEvidence.textContent = fileName || "No file selected";
            });
        }

        form.querySelectorAll("input, textarea, select").forEach(function (field) {
            field.addEventListener("input", syncReview);
            field.addEventListener("change", syncReview);
        });

        function setSearchState(isLoading, message) {
            if (searchLoading) searchLoading.hidden = !isLoading;
            if (searchButton) searchButton.disabled = isLoading;
            if (searchFeedback && message) searchFeedback.textContent = message;
        }

        function updateCoordinates(lat, lng) {
            if (latitudeInput) latitudeInput.value = lat;
            if (longitudeInput) longitudeInput.value = lng;
            if (latitudeDisplay) latitudeDisplay.textContent = Number(lat).toFixed(6);
            if (longitudeDisplay) longitudeDisplay.textContent = Number(lng).toFixed(6);
            syncReview();
        }

        function updateAddress(lat, lng) {
            if (!addressInput) return Promise.resolve();
            setSearchState(true, "Resolving selected address...");
            return fetch("https://nominatim.openstreetmap.org/reverse?format=json&lat=" + lat + "&lon=" + lng)
                .then(function (response) { return response.json(); })
                .then(function (data) {
                    const address = data && data.display_name ? data.display_name : "Address not found";
                    if (addressDisplay) addressDisplay.textContent = address;
                    addressInput.value = address;
                    setSearchState(false, "Address updated from the selected map position.");
                    syncReview();
                })
                .catch(function () {
                    if (addressDisplay) addressDisplay.textContent = "Unable to fetch address";
                    addressInput.value = "Unable to fetch address";
                    setSearchState(false, "Address lookup failed. You can still submit the selected coordinates.");
                    syncReview();
                });
        }

        function setMarker(lat, lng, fetchAddress) {
            if (!map) return;
            if (!marker) {
                marker = L.marker([lat, lng], { draggable: true }).addTo(map);
                marker.on("dragend", function () {
                    const pos = marker.getLatLng();
                    updateCoordinates(pos.lat, pos.lng);
                    updateAddress(pos.lat, pos.lng);
                });
            } else {
                marker.setLatLng([lat, lng]);
            }
            updateCoordinates(lat, lng);
            if (fetchAddress) updateAddress(lat, lng);
        }

        function searchLocation() {
            const query = searchInput ? searchInput.value.trim() : "";
            if (!query) {
                setSearchState(false, "Enter a location name or address to search.");
                return;
            }
            setSearchState(true, "Searching OpenStreetMap for the entered location...");
            fetch("https://nominatim.openstreetmap.org/search?format=json&limit=1&q=" + encodeURIComponent(query))
                .then(function (response) { return response.json(); })
                .then(function (results) {
                    if (!results || !results.length) {
                        setSearchState(false, "No location found. Try a more specific landmark or area.");
                        return;
                    }
                    const result = results[0];
                    const lat = parseFloat(result.lat);
                    const lng = parseFloat(result.lon);
                    if (!map) return;
                    map.setView([lat, lng], 16);
                    setMarker(lat, lng, false);
                    if (addressDisplay) addressDisplay.textContent = result.display_name || "Address found";
                    if (addressInput) addressInput.value = result.display_name || "";
                    setSearchState(false, "Location found. You can drag the marker to refine the exact point.");
                    syncReview();
                })
                .catch(function () {
                    setSearchState(false, "Search failed. Check your connection and try again.");
                });
        }

        if (typeof window.L !== "undefined") {
            try {
                const existingLat = parseFloat(latitudeInput && latitudeInput.value ? latitudeInput.value : "");
                const existingLng = parseFloat(longitudeInput && longitudeInput.value ? longitudeInput.value : "");
                map = L.map("map").setView((!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) ? [existingLat, existingLng] : [17.385, 78.4867], (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) ? 15 : 13);
                L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { attribution: "&copy; OpenStreetMap contributors" }).addTo(map);

                if (!Number.isNaN(existingLat) && !Number.isNaN(existingLng)) {
                    setMarker(existingLat, existingLng, addressInput && addressInput.value === "");
                } else if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(function (position) {
                        map.setView([position.coords.latitude, position.coords.longitude], 15);
                        setMarker(position.coords.latitude, position.coords.longitude, true);
                    });
                }

                map.on("click", function (event) {
                    setMarker(event.latlng.lat, event.latlng.lng, true);
                    setSearchState(false, "Map point selected. Drag the marker if you need to fine-tune the location.");
                });
            } catch (error) {
                console.error("[report-form] map initialization failed", error);
            }
        }

        if (searchButton) searchButton.addEventListener("click", searchLocation);
        if (searchInput) {
            searchInput.addEventListener("keydown", function (event) {
                if (event.key === "Enter") {
                    event.preventDefault();
                    searchLocation();
                }
            });
        }

        syncReview();
        showStep(currentStep);
    } catch (error) {
        console.error("JS ERROR:", error);
    }
});


