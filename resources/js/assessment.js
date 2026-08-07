import Alpine from "alpinejs";

/**
 * Generic client interpreter for the guided assessment wizard.
 *
 * It carries no hardcoded branching logic — it reads the config-defined question
 * tree (passed in from the server) and evaluates each question's `showIf` rules
 * to decide visibility, giving progressive disclosure. Stages with no visible
 * questions for the current branch are skipped entirely (e.g. a Thai citizen
 * never sees the foreign-national "legal basis" or "purpose" stages). Inference
 * itself is NOT done here; the answers are POSTed to the server for scoring.
 */
Alpine.data("assessment", (tree, stageCount, stageNames) => ({
    tree,
    stageCount,
    stageNames,
    currentStage: 1,
    answers: {},
    reviewing: false,
    justSaved: false,
    saveTimer: null,

    init() {
        // Restore in-progress answers for this browser session ("save progress").
        try {
            const saved = sessionStorage.getItem("id5_assessment");
            if (saved) {
                const parsed = JSON.parse(saved);
                this.answers = parsed.answers || {};
                this.currentStage = parsed.currentStage || 1;
            }
        } catch (e) {
            /* ignore corrupt storage */
        }

        // Make sure we land on a stage that actually applies to the answers.
        if (!this.applicableStages.includes(this.currentStage)) {
            this.currentStage = this.applicableStages[0] || 1;
        }

        this.$watch("answers", () => this.persist());
        this.$watch("currentStage", () => this.persist());
    },

    node(id) {
        return this.tree.find((n) => n.id === id);
    },

    /** Mirrors QuestionNode::isVisible on the server, from the same showIf rules. */
    isVisible(id) {
        const n = this.node(id);
        if (!n || !n.showIf) return true;

        return Object.entries(n.showIf).every(([key, allowed]) => {
            const a = this.answers[key];
            if (Array.isArray(a)) return a.some((v) => allowed.includes(v));
            return allowed.includes(a);
        });
    },

    hasVisibleQuestions(stage) {
        return this.tree.some((n) => n.stage === stage && this.isVisible(n.id));
    },

    /** Stage numbers that currently have at least one visible question. */
    get applicableStages() {
        const stages = [];
        for (let s = 1; s <= this.stageCount; s++) {
            if (this.hasVisibleQuestions(s)) stages.push(s);
        }
        return stages;
    },

    get progressIndex() {
        return Math.max(0, this.applicableStages.indexOf(this.currentStage));
    },

    get isFirstStage() {
        return this.progressIndex === 0;
    },

    get isLastStage() {
        return this.progressIndex === this.applicableStages.length - 1;
    },

    stageName(stage) {
        return this.stageNames[stage] || "";
    },

    select(question, value, type) {
        if (type === "multi") {
            const current = Array.isArray(this.answers[question])
                ? [...this.answers[question]]
                : [];
            const idx = current.indexOf(value);
            if (idx === -1) current.push(value);
            else current.splice(idx, 1);
            this.answers[question] = current;
        } else {
            this.answers[question] = value;
            // A changed single-choice answer can hide downstream branches —
            // drop any answers that are no longer visible so they don't linger.
            this.pruneHidden();
        }
    },

    isSelected(question, value) {
        const a = this.answers[question];
        return Array.isArray(a) ? a.includes(value) : a === value;
    },

    /**
     * Roving tabindex for a single-choice option: only the selected option is
     * tab-reachable; if nothing is selected yet, the first option is. Everything
     * else is -1 and reached via arrow keys (proper WCAG radiogroup behaviour).
     */
    rovingTab(question, value, isFirst) {
        if (this.isSelected(question, value)) return 0;
        const answered =
            this.answers[question] !== undefined &&
            this.answers[question] !== null &&
            this.answers[question] !== "";
        return !answered && isFirst ? 0 : -1;
    },

    /** Arrow-key navigation within a radiogroup: move focus + selection. */
    focusSibling(event, dir) {
        const group = event.currentTarget.closest("fieldset");
        if (!group) return;
        const radios = Array.from(group.querySelectorAll('[role="radio"]'));
        const idx = radios.indexOf(event.currentTarget);
        if (idx === -1) return;
        const next = radios[(idx + dir + radios.length) % radios.length];
        if (next) {
            next.focus();
            next.click();
        }
    },

    pruneHidden() {
        let changed = true;
        while (changed) {
            changed = false;
            Object.keys(this.answers).forEach((id) => {
                if (!this.isVisible(id)) {
                    delete this.answers[id];
                    changed = true;
                }
            });
        }
    },

    /** Visible questions belonging to the current stage. */
    visibleStageNodes() {
        return this.tree.filter(
            (n) => n.stage === this.currentStage && this.isVisible(n.id)
        );
    },

    /** Every visible question in the current stage must be answered. */
    get canContinue() {
        return this.visibleStageNodes().every((n) => {
            const a = this.answers[n.id];
            return Array.isArray(a)
                ? a.length > 0
                : a !== undefined && a !== null && a !== "";
        });
    },

    next() {
        if (!this.canContinue || this.isLastStage) return;
        this.currentStage = this.applicableStages[this.progressIndex + 1];
        this.focusStage();
    },

    back() {
        if (this.isFirstStage) return;
        this.currentStage = this.applicableStages[this.progressIndex - 1];
        this.focusStage();
    },

    /** Show the pre-submit "Review Your Answers" screen instead of submitting directly. */
    startReview() {
        if (!this.canContinue) return;
        this.reviewing = true;
        this.$nextTick(() => {
            document.getElementById("review-heading")?.focus();
            window.scrollTo({ top: 0, behavior: "smooth" });
        });
    },

    backToEdit() {
        this.reviewing = false;
    },

    /** Jump back to a specific stage to change an answer, from the review screen. */
    editStage(stage) {
        this.reviewing = false;
        this.currentStage = stage;
        this.focusStage();
    },

    /**
     * Human-readable recap of every visible, answered question — built from
     * the same tree + showIf rules the wizard itself uses, so it always
     * matches exactly what the user actually saw and answered.
     */
    get reviewRecap() {
        return this.tree
            .filter((n) => this.isVisible(n.id) && this.answers[n.id] !== undefined)
            .map((n) => {
                const raw = this.answers[n.id];
                const values = Array.isArray(raw) ? raw : [raw];
                const labels = values
                    .map((v) => n.options.find((o) => o.value === v)?.label)
                    .filter(Boolean);

                return labels.length
                    ? { stage: n.stage, question: n.title, answers: labels }
                    : null;
            })
            .filter(Boolean);
    },

    focusStage() {
        this.$nextTick(() => {
            const heading = document.getElementById(
                `stage-${this.currentStage}-heading`
            );
            if (heading) heading.focus();

            const reduce =
                window.matchMedia &&
                window.matchMedia("(prefers-reduced-motion: reduce)").matches;
            window.scrollTo({ top: 0, behavior: reduce ? "auto" : "smooth" });
        });
    },

    persist() {
        try {
            sessionStorage.setItem(
                "id5_assessment",
                JSON.stringify({
                    answers: this.answers,
                    currentStage: this.currentStage,
                })
            );
            this.showSavedIndicator();
        } catch (e) {
            /* storage may be unavailable (private mode) — non-fatal */
        }
    },

    /** Brief "Progress saved" indicator so auto-save is visible, not silent. */
    showSavedIndicator() {
        this.justSaved = true;
        clearTimeout(this.saveTimer);
        this.saveTimer = setTimeout(() => {
            this.justSaved = false;
        }, 2000);
    },

    submit() {
        if (!this.canContinue) return;
        this.reviewing = false;
        this.$refs.answersField.value = JSON.stringify(this.answers);
        // Clear saved progress so a fresh visit starts clean.
        try {
            sessionStorage.removeItem("id5_assessment");
        } catch (e) {
            /* ignore */
        }
        this.$refs.resultForm.submit();
    },
}));
