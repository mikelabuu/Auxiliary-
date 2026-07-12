import './bootstrap';

const AAIS_STORE_KEY = 'aais-demo-documents-v1';

const AAIS_STATUS_LABELS = {
	logged: 'Logged',
	process: 'In Process',
	approved: 'Approved',
	pickup: 'For Pickup',
	complete: 'Completed',
	void: 'Voided',
};

const TRACKER_FLOW = ['logged', 'process', 'approved', 'pickup', 'complete'];

function normalizeReference(ref) {
	return String(ref || '').trim().toUpperCase();
}

function readStore() {
	try {
		const raw = window.localStorage.getItem(AAIS_STORE_KEY);
		if (!raw) {
			return [];
		}

		const parsed = JSON.parse(raw);
		return Array.isArray(parsed) ? parsed : [];
	} catch (error) {
		return [];
	}
}

function writeStore(records) {
	try {
		window.localStorage.setItem(AAIS_STORE_KEY, JSON.stringify(records));
		window.dispatchEvent(new CustomEvent('aais-demo-store-updated', {
			detail: { key: AAIS_STORE_KEY, count: records.length },
		}));
	} catch (error) {
		// localStorage may be unavailable in strict environments; fail quietly for demo mode.
	}
}

function formatDate(iso) {
	if (!iso) {
		return '-';
	}

	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) {
		return '-';
	}

	return new Intl.DateTimeFormat('en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
	}).format(date);
}

function formatDateTime(iso) {
	if (!iso) {
		return '-';
	}

	const date = new Date(iso);
	if (Number.isNaN(date.getTime())) {
		return '-';
	}

	return new Intl.DateTimeFormat('en-US', {
		month: 'short',
		day: 'numeric',
		year: 'numeric',
		hour: 'numeric',
		minute: '2-digit',
	}).format(date);
}

function cloneRecord(record) {
	return JSON.parse(JSON.stringify(record));
}

function buildTimeline(record) {
	const statusIndex = TRACKER_FLOW.indexOf(record.status);
	const submittedMeta = `${formatDateTime(record.submittedAt)} by client`;
	const receivedMeta = record.acceptedAt
		? `${formatDateTime(record.acceptedAt)} by ${record.staff || 'staff'}`
		: 'Your submission is in queue for staff confirmation';

	return [
		{
			title: 'Document submitted via kiosk',
			meta: submittedMeta,
			done: true,
			last: false,
			active: false,
		},
		{
			title: 'Accepted by receiving staff',
			meta: receivedMeta,
			done: !!record.accepted,
			last: false,
			active: !record.accepted,
		},
		{
			title: 'Processing by assigned office',
			meta: statusIndex >= 1
				? `Current status: ${AAIS_STATUS_LABELS[record.status] || 'In Process'}`
				: 'Pending acceptance',
			done: statusIndex >= 2,
			last: false,
			active: statusIndex === 1,
		},
		{
			title: 'Approval and final validation',
			meta: statusIndex >= 2 ? 'Reviewed by office staff' : 'Waiting for processing update',
			done: statusIndex >= 3,
			last: false,
			active: statusIndex === 2,
		},
		{
			title: 'Ready for pickup',
			meta: statusIndex >= 3 ? 'Client notification stage' : 'Not yet ready',
			done: statusIndex >= 4,
			last: false,
			active: statusIndex === 3,
		},
		{
			title: 'Document released',
			meta: statusIndex >= 4 ? 'Completed' : 'Awaiting release',
			done: statusIndex >= 4,
			last: true,
			active: false,
		},
	];
}

function createAaisDemoStore() {
	return {
		statusLabels: AAIS_STATUS_LABELS,

		formatDate,
		formatDateTime,

		listRecords() {
			return readStore().map(cloneRecord);
		},

		listPending() {
			return this.listRecords().filter((doc) => !doc.accepted);
		},

		listAccepted() {
			return this.listRecords().filter((doc) => doc.accepted);
		},

		getRecentAccepted(limit = 5) {
			return this.listAccepted()
				.sort((left, right) => {
					const leftTime = new Date(left.acceptedAt || left.updatedAt || left.submittedAt).getTime();
					const rightTime = new Date(right.acceptedAt || right.updatedAt || right.submittedAt).getTime();
					return rightTime - leftTime;
				})
				.slice(0, limit);
		},

		findByReference(reference) {
			const normalized = normalizeReference(reference);
			if (!normalized) {
				return null;
			}

			const record = readStore().find((doc) => doc.ref === normalized);
			return record ? cloneRecord(record) : null;
		},

		generateReference() {
			const year = new Date().getFullYear();
			const existing = new Set(readStore().map((doc) => doc.ref));

			for (let attempt = 0; attempt < 50; attempt += 1) {
				const suffix = String(Math.floor(Math.random() * 9000) + 1000);
				const candidate = `TL-${year}-${suffix}`;
				if (!existing.has(candidate)) {
					return candidate;
				}
			}

			const fallback = String(Date.now()).slice(-4);
			return `TL-${year}-${fallback}`;
		},

		createSubmission(payload) {
			const records = readStore();
			const reference = normalizeReference(payload?.ref || this.generateReference());
			const nowIso = new Date().toISOString();
			const attachmentName = String(payload?.attachment?.name || '').trim();
			const attachmentType = String(payload?.attachment?.type || '').trim();
			const attachmentSizeMb = String(payload?.attachment?.sizeMb || '').trim();
			const attachmentUrl = String(payload?.attachment?.url || '').trim();

			if (!reference) {
				return null;
			}

			if (records.some((doc) => doc.ref === reference)) {
				return null;
			}

			const record = {
				ref: reference,
				name: String(payload?.name || '').trim() || 'Walk-in Client',
				type: String(payload?.type || '').trim() || 'General Document',
				purpose: String(payload?.purpose || '').trim() || 'General Request',
				office: String(payload?.office || '').trim() || 'Registrar Office',
				status: 'logged',
				accepted: false,
				staff: '',
				next: 'Receiving Clerk',
				remarks: '',
				attachmentName,
				attachmentType,
				attachmentSizeMb,
				attachmentUrl,
				submittedAt: nowIso,
				acceptedAt: null,
				receivedAt: null,
				updatedAt: nowIso,
				history: [
					{
						at: nowIso,
						status: 'logged',
						note: 'Submitted via kiosk form',
					},
				],
			};

			records.unshift(record);
			writeStore(records);

			return cloneRecord(record);
		},

		acceptDocument(reference, updates = {}) {
			const normalized = normalizeReference(reference);
			const records = readStore();
			const idx = records.findIndex((doc) => doc.ref === normalized);

			if (idx < 0) {
				return null;
			}

			const nowIso = new Date().toISOString();
			const record = records[idx];

			if (!record.accepted) {
				record.accepted = true;
				record.acceptedAt = nowIso;
				record.receivedAt = nowIso;
				record.status = record.status === 'logged' ? 'process' : record.status;
				record.staff = String(updates.staff || '').trim() || record.staff || 'Receiving Clerk';
				record.next = String(updates.next || '').trim() || record.next || 'Processing Desk';
				record.office = String(updates.office || '').trim() || record.office || 'Registrar Office';
				record.history = Array.isArray(record.history) ? record.history : [];
				record.history.push({
					at: nowIso,
					status: record.status,
					note: `Accepted by ${record.staff}`,
				});
			}

			record.updatedAt = nowIso;
			records[idx] = record;
			writeStore(records);

			return cloneRecord(record);
		},

		updateReview(reference, updates = {}) {
			const normalized = normalizeReference(reference);
			const records = readStore();
			const idx = records.findIndex((doc) => doc.ref === normalized);

			if (idx < 0) {
				return null;
			}

			const record = records[idx];
			const nowIso = new Date().toISOString();
			const nextStatus = String(updates.status || '').trim();
			const nextRemarks = updates.remarks;
			const nextStaff = String(updates.staff || '').trim();
			const nextRoute = String(updates.next || '').trim();

			if (nextStatus && Object.prototype.hasOwnProperty.call(AAIS_STATUS_LABELS, nextStatus) && nextStatus !== record.status) {
				record.status = nextStatus;
				record.history = Array.isArray(record.history) ? record.history : [];
				record.history.push({
					at: nowIso,
					status: nextStatus,
					note: `Status changed to ${AAIS_STATUS_LABELS[nextStatus]}`,
				});
			}

			if (typeof nextRemarks === 'string') {
				record.remarks = nextRemarks.trim();
			}

			if (nextStaff) {
				record.staff = nextStaff;
			}

			if (nextRoute) {
				record.next = nextRoute;
			}

			record.updatedAt = nowIso;
			records[idx] = record;
			writeStore(records);

			return cloneRecord(record);
		},

		getTrackerPayload(reference) {
			const record = this.findByReference(reference);
			if (!record) {
				return null;
			}

			const statusLabel = AAIS_STATUS_LABELS[record.status] || 'Unknown';

			return {
				ref: record.ref,
				name: record.name,
				type: record.type,
				office: record.office || 'Registrar Office',
				staff: record.staff || 'Pending staff assignment',
				status: record.status,
				statusLabel,
				remarks: record.remarks || '',
				accepted: !!record.accepted,
				submittedDisplay: formatDateTime(record.submittedAt),
				acceptedDisplay: record.acceptedAt ? formatDateTime(record.acceptedAt) : 'Awaiting acceptance',
				updatedAt: record.updatedAt || record.acceptedAt || record.submittedAt || null,
				updatedDisplay: formatDateTime(record.updatedAt || record.acceptedAt || record.submittedAt),
				timeline: buildTimeline(record),
			};
		},
	};
}

window.AAISDemoStore = createAaisDemoStore();
