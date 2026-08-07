import "./bootstrap";

import Alpine from "alpinejs";
import Focus from "@alpinejs/focus";
import "./office-locator";
import "./assessment";

Alpine.plugin(Focus);
window.Alpine = Alpine;
Alpine.start();
