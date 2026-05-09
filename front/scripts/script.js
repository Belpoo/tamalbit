document.addEventListener("DOMContentLoaded", () => {
	const personIdForm = document.querySelector("#personIdForm");
	const personIdInput = document.querySelector("#personIdInput");
	const alertsContainer = document.querySelector("#alertsContainer");
	const productosContainer = document.querySelector("#productosContainer");
	const historialBody = document.querySelector("#historialBody");
	const metricUsuario = document.querySelector("#metricUsuario");
	const metricSaldo = document.querySelector("#metricSaldo");
	const metricGastado = document.querySelector("#metricGastado");
	const metricTamalbits = document.querySelector("#metricTamalbits");
	const metricSaldoInicial = document.querySelector("#metricSaldoInicial");
	const idRegex = /^\d{6,20}$/;
	const url = new URL(window.location.href);
	const queryStatus = (url.searchParams.get("status") || "").trim();
	const queryMsg = (url.searchParams.get("msg") || "").trim();
	const queryPersonId = (url.searchParams.get("personId") || "").trim();

	const formatCurrency = (value) => {
		return `$${Number(value || 0).toFixed(2)}`;
	};

	const fallbackImage = "data:image/svg+xml," + encodeURIComponent(`
		<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 640 360'>
			<defs>
				<linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>
					<stop offset='0%' stop-color='#f7e5c5' />
					<stop offset='100%' stop-color='#ffd2a2' />
				</linearGradient>
			</defs>
			<rect width='640' height='360' fill='url(#g)' />
			<circle cx='320' cy='150' r='68' fill='#fff4df' stroke='#d79a5b' stroke-width='8' />
			<rect x='210' y='246' width='220' height='26' rx='13' fill='#d79a5b' />
			<text x='320' y='330' text-anchor='middle' font-size='30' fill='#6c3f1d' font-family='Trebuchet MS, Arial'>Sin imagen</text>
		</svg>
	`);

	const escapeHtml = (value) => {
		return String(value ?? "")
			.replaceAll("&", "&amp;")
			.replaceAll("<", "&lt;")
			.replaceAll(">", "&gt;")
			.replaceAll('"', "&quot;")
			.replaceAll("'", "&#39;");
	};

	const addAlert = (type, message) => {
		if (!alertsContainer || !message) {
			return;
		}

		const alert = document.createElement("div");
		alert.className = `alert ${type} mb-3`;
		alert.textContent = message;
		alertsContainer.appendChild(alert);
	};

	const setMetrics = (metrics = {}, usuarioLabel = "-") => {
		if (!metricUsuario) {
			return;
		}

		metricUsuario.textContent = usuarioLabel;
		metricSaldo.textContent = formatCurrency(metrics.saldo);
		metricGastado.textContent = formatCurrency(metrics.totalGastado);
		metricTamalbits.textContent = `${Number(metrics.totalTamalbits || 0)} 🪙`;
		metricSaldoInicial.textContent = formatCurrency(metrics.saldoInicialEstimado);
	};

	const resolveProductImage = (producto) => {
		const fallbackByProductName = {
			"orejas de pollo": "orejas-de-pollo.jpg",
			"patas de zancudo": "patas-de-zancudo.jpg",
			"hamburguesa": "hamburguesa.jpg",
			"pizza": "pizza.jpg",
			"gaseosa": "gaseosa.jpg",
		};

		const rawName = String(producto.imagen_producto || producto.imagen_archivo || "").trim();
		const isSafeFileName = /^[a-zA-Z0-9._-]+$/.test(rawName);
		const fallbackName = fallbackByProductName[String(producto.nombre || "").toLowerCase()] || "";

		if (isSafeFileName) {
			return `../images/${encodeURIComponent(rawName)}`;
		}

		if (/^[a-zA-Z0-9._-]+$/.test(fallbackName)) {
			return `../images/${encodeURIComponent(fallbackName)}`;
		}

		return fallbackImage;
	};

	const renderProductos = (productos, personId, allowPurchase) => {
		if (!productosContainer) {
			return;
		}

		if (!productos || productos.length === 0) {
			productosContainer.innerHTML = `<div class="col-12"><div class="alert alert-warning mb-0">No hay productos cargados en la base de datos.</div></div>`;
			return;
		}

		productosContainer.innerHTML = productos.map((producto) => {
			const inputId = `desc-${producto.id}`;
			const disabledAttr = allowPurchase ? "" : "disabled";
			const imageSrc = resolveProductImage(producto);

			return `
				<div class="col-12 col-md-6 col-lg-4">
					<article class="product-card reveal-up">
						<div class="product-media-wrap mb-3">
							<img class="product-image" src="${imageSrc}" alt="Imagen de ${escapeHtml(producto.nombre)}" loading="lazy">
						</div>

						<div class="d-flex justify-content-between align-items-start gap-2 mb-2">
							<h3 class="h5 mb-0">${escapeHtml(producto.nombre)}</h3>
							<span class="badge rounded-pill text-bg-light">${escapeHtml(producto.categoria)}</span>
						</div>

						<p class="display-6 fw-bold price-tag mb-3">${formatCurrency(producto.precio)}</p>

						<form action="../back/comprar.php" method="POST" class="purchase-form">
							<input type="hidden" name="producto_id" value="${Number(producto.id)}">
							<input type="hidden" name="person_id" value="${escapeHtml(personId)}">

							<label class="form-label" for="${inputId}">Descripción (opcional)</label>
							<input
								id="${inputId}"
								type="text"
								name="descripcion"
								maxlength="255"
								class="form-control mb-3"
								placeholder="Ej: compra para almuerzo"
							>

							<button class="btn btn-dark w-100" ${disabledAttr}>Comprar</button>
						</form>
					</article>
				</div>
			`;
		}).join("");
	};

	const renderHistorial = (gastos) => {
		if (!historialBody) {
			return;
		}

		if (!gastos || gastos.length === 0) {
			historialBody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-4">Sin gastos registrados para este personId.</td></tr>`;
			return;
		}

		historialBody.innerHTML = gastos.map((gasto) => {
			return `
				<tr>
					<td>${escapeHtml(gasto.nombre_usuario)}</td>
					<td>${escapeHtml(gasto.producto)}</td>
					<td>${escapeHtml(gasto.categoria)}</td>
					<td>${formatCurrency(gasto.monto)}</td>
					<td>${escapeHtml(gasto.descripcion)}</td>
					<td>${Number(gasto.tamalbits || 0)} 🪙</td>
					<td>${escapeHtml(gasto.fecha)}</td>
				</tr>
			`;
		}).join("");
	};

	const applyRevealAnimation = () => {
		const items = document.querySelectorAll(".reveal-up");

		items.forEach((item, index) => {
			setTimeout(() => {
				item.classList.add("visible");
			}, index * 70);
		});
	};

	const wireImageFallbacks = () => {
		document.querySelectorAll(".product-image").forEach((img) => {
			img.addEventListener("error", () => {
				img.src = fallbackImage;
			});
		});
	};

	const loadDashboard = async (personId) => {
		alertsContainer.innerHTML = "";

		if (queryMsg) {
			addAlert(queryStatus === "ok" ? "alert-success" : "alert-warning", queryMsg);
		}

		if (!personId) {
			addAlert("alert-info", "Ingresa un personId para consultar saldo y habilitar compras.");
		}

		try {
			const endpoint = personId
				? `../api/dashboard_data.php?personId=${encodeURIComponent(personId)}`
				: "../api/dashboard_data.php";
			const response = await fetch(endpoint, {
				headers: { "Accept": "application/json" },
				cache: "no-store",
			});
			const payload = await response.json();

			if (payload.personIdError) {
				addAlert("alert-warning", payload.personIdError);
			}

			if (payload.apiError) {
				addAlert("alert-danger", payload.apiError);
			}

			const activePersonId = payload.personId || personId;
			setMetrics(payload.metrics, activePersonId ? payload.metrics.nombre : "-");

			const canPurchase = Boolean(activePersonId) && !payload.apiError;
			renderProductos(payload.productos || [], activePersonId, canPurchase);
			wireImageFallbacks();
			renderHistorial(payload.gastos || []);
			applyRevealAnimation();
		} catch (error) {
			addAlert("alert-danger", "No fue posible cargar los datos de la aplicacion.");
			setMetrics({}, "-");
			renderProductos([], "", false);
			renderHistorial([]);
		}
	};

	if (personIdInput) {
		const savedPersonId = (localStorage.getItem("tamalbit_person_id") || "").trim();

		if (queryPersonId && idRegex.test(queryPersonId)) {
			personIdInput.value = queryPersonId;
		} else if (!queryPersonId && savedPersonId && idRegex.test(savedPersonId)) {
			personIdInput.value = savedPersonId;
		}
	}

	if (personIdForm && personIdInput) {
		personIdForm.addEventListener("submit", (event) => {
			const value = personIdInput.value.trim();

			if (!idRegex.test(value)) {
				event.preventDefault();
				personIdInput.setCustomValidity("Ingresa un codigo numerico valido (6 a 20 digitos).");
				personIdInput.reportValidity();
				return;
			}

			personIdInput.setCustomValidity("");
			personIdInput.value = value;
			localStorage.setItem("tamalbit_person_id", value);
		});
	}

	const initialPersonId = personIdInput ? personIdInput.value.trim() : "";
	loadDashboard(initialPersonId);
});
