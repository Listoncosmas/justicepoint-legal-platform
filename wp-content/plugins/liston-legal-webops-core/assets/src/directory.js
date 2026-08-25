import maplibregl from "maplibre-gl";

const directories = document.querySelectorAll("[data-jp-directory]");

directories.forEach((directory) => {
  const mapElement = directory.querySelector("[data-jp-map]");
  const listElement = directory.querySelector("[data-jp-office-list]");
  const statusElement = directory.querySelector(".jp-directory__status");
  const form = directory.querySelector("form");
  let map;
  let markers = [];

  const createMap = () => {
    if (!mapElement || map) return;
    map = new maplibregl.Map({
      container: mapElement,
      style: {
        version: 8,
        sources: {
          osm: {
            type: "raster",
            tiles: ["https://tile.openstreetmap.org/{z}/{x}/{y}.png"],
            tileSize: 256,
            attribution: "© OpenStreetMap contributors",
          },
        },
        layers: [{ id: "osm", type: "raster", source: "osm" }],
      },
      center: [-118.244, 34.052],
      zoom: 8.6,
      cooperativeGestures: true,
    });
    map.addControl(
      new maplibregl.NavigationControl({ showCompass: false }),
      "top-right",
    );
  };

  const renderMap = (offices) => {
    createMap();
    markers.forEach((marker) => marker.remove());
    markers = [];
    const bounds = new maplibregl.LngLatBounds();
    offices.forEach((office, index) => {
      if (
        !Number.isFinite(Number(office.latitude)) ||
        !Number.isFinite(Number(office.longitude))
      )
        return;
      const element = document.createElement("button");
      element.type = "button";
      element.className = "jp-map-marker";
      element.textContent = String(index + 1);
      element.setAttribute("aria-label", `Show ${office.title} on map`);
      const popupContent = document.createElement("a");
      popupContent.href = office.url;
      popupContent.textContent = office.title;
      const popup = new maplibregl.Popup({ offset: 22 }).setDOMContent(
        popupContent,
      );
      const marker = new maplibregl.Marker({ element })
        .setLngLat([Number(office.longitude), Number(office.latitude)])
        .setPopup(popup)
        .addTo(map);
      markers.push(marker);
      bounds.extend([Number(office.longitude), Number(office.latitude)]);
    });
    if (!bounds.isEmpty())
      map.fitBounds(bounds, {
        padding: 70,
        maxZoom: 11,
        duration: window.matchMedia("(prefers-reduced-motion: reduce)").matches
          ? 0
          : 500,
      });
  };

  const officeCard = (office) => {
    const article = document.createElement("article");
    article.className = "jp-office-row";
    article.innerHTML =
      '<div class="jp-office-row__index" aria-hidden="true"></div>';
    const content = document.createElement("div");
    const eyebrow = document.createElement("p");
    eyebrow.className = "jp-eyebrow";
    eyebrow.textContent = "JusticePoint office";
    const heading = document.createElement("h2");
    const link = document.createElement("a");
    link.href = office.url;
    link.textContent = office.title;
    heading.append(link);
    const address = document.createElement("address");
    address.append(
      document.createTextNode(office.address),
      document.createElement("br"),
      document.createTextNode(`${office.city}, ${office.state} ${office.zip}`),
    );
    content.append(eyebrow, heading, address);
    const actions = document.createElement("div");
    actions.className = "jp-office-row__actions";
    const phone = document.createElement("a");
    phone.className = "jp-text-link";
    phone.href = `tel:${office.telephone_uri}`;
    phone.textContent = office.telephone;
    const consultation = document.createElement("a");
    consultation.className = "jp-button jp-button--small";
    consultation.href = office.consultation_url;
    consultation.textContent = "Request consultation";
    actions.append(phone, consultation);
    article.append(content, actions);
    return article;
  };

  const update = async () => {
    const values = new FormData(form);
    const params = new URLSearchParams();
    values.forEach((value, key) => {
      if (value) params.set(key, value);
    });
    params.set("per_page", "50");
    statusElement.textContent = "Updating office results…";
    directory.setAttribute("aria-busy", "true");
    try {
      const response = await fetch(
        `${directory.dataset.endpoint}?${params.toString()}`,
        { headers: { Accept: "application/json" } },
      );
      if (!response.ok)
        throw new Error("Office results are temporarily unavailable.");
      const offices = await response.json();
      listElement.replaceChildren(...offices.map(officeCard));
      const total = Number(
        response.headers.get("X-WP-Total") || offices.length,
      );
      statusElement.innerHTML = `<strong>${total}</strong> office${total === 1 ? "" : "s"} found`;
      params.delete("per_page");
      const nextUrl = `${window.location.pathname}${params.toString() ? `?${params}` : ""}`;
      window.history.replaceState({}, "", nextUrl);
      renderMap(offices);
    } catch (error) {
      statusElement.textContent = error.message;
    } finally {
      directory.removeAttribute("aria-busy");
    }
  };

  form.addEventListener("submit", (event) => {
    event.preventDefault();
    update();
  });
  createMap();
  const initialOffices = Array.from(
    listElement.querySelectorAll("[data-office-card]"),
  ).map((card) => ({
    title: card.dataset.title,
    url: card.querySelector("h2 a")?.href || "",
    latitude: Number(card.dataset.lat),
    longitude: Number(card.dataset.lng),
  }));
  renderMap(initialOffices);
});
