<template>
	<div class="flex flex-col gap-4">
		<div v-if="loading" class="flex flex-col items-center justify-center gap-2 text-fontcolor-soft">
			<ns-spinner size="24" border="4"></ns-spinner>
			<span>{{ __('Loading accounting rules...') }}</span>
		</div>

		<template v-else>
			<div class="rounded-lg border border-box-edge bg-box-background p-4 text-fontcolor shadow"
				:aria-label="__('Search accounting rules')">
				<label for="transaction-rule-search" class="mb-2 block text-sm font-medium">
					{{ __('Search Rules') }}
				</label>
				<div class="relative">
					<i class="las la-search pointer-events-none absolute start-3 top-1/2 -translate-y-1/2 text-fontcolor-soft"
						aria-hidden="true"></i>
					<input id="transaction-rule-search" v-model="searchQuery" type="search"
						class="h-10 w-full rounded border border-input-edge bg-input-background py-2 ps-10 pe-10 text-fontcolor outline-none placeholder:text-fontcolor-soft focus:border-primary focus:ring-2 focus:ring-primary/20"
						:placeholder="__('Search events or amount sources...')" />
					<button v-if="searchQuery" type="button"
						class="absolute end-0 top-0 flex h-10 w-10 items-center justify-center rounded text-fontcolor-soft hover:bg-input-button-hover hover:text-fontcolor focus-visible:outline-2 focus-visible:outline-primary"
						:aria-label="__('Clear rule search')" @click="searchQuery = ''">
						<i class="las la-times" aria-hidden="true"></i>
					</button>
				</div>
				<p class="mt-2 text-sm text-fontcolor-soft" aria-live="polite">
					{{
						__('Showing {visible} of {total} rule groups')
							.replace('{visible}', String(filteredGroups.length))
							.replace('{total}', String(groups.length))
					}}
				</p>
			</div>

			<div v-if="filteredGroups.length === 0" class="rounded-lg border border-dashed border-box-edge bg-box-background p-8 text-center text-fontcolor-soft">
				{{ __('No accounting rules match your search.') }}
			</div>
			<div v-for="group in filteredGroups" :key="group.on" class="overflow-hidden rounded-lg border border-box-edge bg-box-background text-fontcolor shadow">
				<div class="flex items-center justify-between">
					<div
						class="flex flex-col gap-3 p-4 sm:flex-row sm:items-start sm:justify-between"
						:class="group.collapsed ? '' : 'border-b border-box-edge'">
						<div class="min-w-0">
							<h3 class="font-semibold">{{ events[group.on]?.label || group.on }}</h3>
							<p class="mt-1 text-sm text-fontcolor-soft">
								{{ events[group.on]?.description || __('Configure the accounting actions for this event.')
								}}
							</p>
						</div>
					</div>
					<div class="flex shrink-0 flex-wrap justify-end gap-2 p-4">
						<div class="ns-button default">
							<button type="button" class="rounded px-3 py-2" :aria-expanded="!group.collapsed"
								:aria-controls="'transaction-rule-group-' + group.on"
								@click="group.collapsed = !group.collapsed">
								<i class="las la-angle-down transition-transform"
									:class="group.collapsed ? '' : 'rotate-180'" aria-hidden="true"></i>
								{{ group.collapsed ? __('Expand') : __('Collapse') }}
							</button>
						</div>
						<div class="ns-button info">
							<button type="button" class="rounded px-3 py-2"
								:disabled="group.saving || !isBalanced(group)" @click="saveGroup(group)">
								<i class="las la-save" aria-hidden="true"></i>
								{{ group.saving ? __('Saving...') : __('Save Group') }}
							</button>
						</div>
						<div v-if="!group.locked" class="ns-button error">
							<button type="button" class="rounded px-3 py-2" :disabled="group.saving"
								@click="confirmDeleteGroup(group)">
								<i class="lar la-trash-alt" aria-hidden="true"></i>
								<span>{{ __('Delete group') }}</span>
							</button>
						</div>
					</div>
				</div>
				<div :id="'transaction-rule-group-' + group.on" v-if="!group.collapsed" class="flex flex-col gap-3">
					<div class="p-4">
						<div v-for="(line, index) in group.lines" :key="line.local_key || line.id" class="md:grid-cols-[2fr_1fr_2fr_auto] grid grid-cols-1 gap-3 rounded-lg border border-box-elevation-edge bg-box-elevation-background p-3">
							<label class="flex min-w-0 flex-col gap-1 text-sm">
								<span class="font-medium">{{ __('Account') }}</span>
								<select
									class="h-10 w-full rounded border border-input-edge bg-input-background px-3 text-fontcolor outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-input-disabled"
									:value="accountSelection(line)" :disabled="group.saving"
									@change="setAccountSelection(line, $event)">
									<option value="">{{ __('Choose an account') }}</option>
									<optgroup :label="__('Accounts')">
										<option v-for="account in accounts" :key="account.id"
											:value="'account:' + account.id">
											{{ account.account }} - {{ account.name }}
										</option>
									</optgroup>
									<optgroup v-if="roleEntries(group.on).length" :label="__('Dynamic Accounts')">
										<option v-for="[role, definition] in roleEntries(group.on)" :key="role"
											:value="'role:' + role">
											{{ definition.label }}
										</option>
									</optgroup>
								</select>
							</label>

							<label class="flex flex-col gap-1 text-sm">
								<span class="font-medium">{{ __('Effect') }}</span>
								<select v-model="line.effect" :disabled="group.saving"
									class="h-10 rounded border border-input-edge bg-input-background px-3 text-fontcolor outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:bg-input-disabled">
									<option value="increase">{{ __('Increase') }}</option>
									<option value="decrease">{{ __('Decrease') }}</option>
								</select>
								<span class="text-xs text-fontcolor-soft">{{ operationFor(line, group.on) }}</span>
							</label>

							<label class="flex min-w-0 flex-col gap-1 text-sm">
								<span class="font-medium">{{ __('Amount Source') }}</span>
								<select v-model="line.amount_source" :disabled="group.saving"
									class="h-10 w-full rounded border border-input-edge bg-input-background px-3 text-fontcolor outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 disabled:bg-input-disabled">
									<option v-for="[source, label] in sourceEntries(group.on)" :key="source"
										:value="source">
										{{ label }}
									</option>
								</select>
							</label>

							<div class="flex justify-center flex-col">
								<div class="flex items-end justify-end gap-1">
									<button type="button"
										class="h-10 w-10 rounded border border-input-edge bg-input-button text-fontcolor hover:bg-input-button-hover focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-input-disabled"
										:disabled="group.saving || index === 0" :aria-label="__('Move action up')"
										@click="moveLine(group, index, -1)">
										<i class="las la-arrow-up" aria-hidden="true"></i>
									</button>
									<button type="button"
										class="h-10 w-10 rounded border border-input-edge bg-input-button text-fontcolor hover:bg-input-button-hover focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-input-disabled"
										:disabled="group.saving || index === group.lines.length - 1"
										:aria-label="__('Move action down')" @click="moveLine(group, index, 1)">
										<i class="las la-arrow-down" aria-hidden="true"></i>
									</button>
									<button type="button"
										class="h-10 w-10 rounded border border-input-edge bg-input-button text-fontcolor hover:bg-input-button-hover focus:outline-none focus:ring-2 focus:ring-primary/20 disabled:cursor-not-allowed disabled:bg-input-disabled"
										:disabled="group.saving || group.lines.length <= 2"
										:aria-label="__('Remove action')" @click="confirmRemoveLine(group, line)">
										<i class="las la-times" aria-hidden="true"></i>
									</button>
								</div>
							</div>
						</div>

						<div v-if="group.error"
							class="rounded border border-error-primary bg-box-elevation-background p-3 text-sm text-error-tertiary"
							role="alert">
							{{ group.error }}
						</div>
					</div>

					<div
						class="flex flex-col p-4 gap-3 border-t border-box-edge pt-4 sm:flex-row sm:items-center sm:justify-between">
						<div class="text-sm">
							<span class="font-medium mr-2">{{ __('Balance') }}:</span>
							<span :class="isBalanced(group) ? 'text-success-tertiary' : 'text-error-tertiary'">
								{{ balanceSummary(group) }}
							</span>
						</div>
						<div class="ns-button default">
							<button type="button" class="rounded px-3 py-2" :disabled="group.saving"
								@click="addLine(group)">
								<i class="las la-plus" aria-hidden="true"></i>
								{{ __('Add Action') }}
							</button>
						</div>
					</div>
				</div>
			</div>
		</template>
	</div>
</template>

<script lang="ts">
import { forkJoin } from 'rxjs';
import { nsSnackBar } from '~/bootstrap';
import { nsConfirmPopup } from '~/components/components';

declare const __: (text: string) => string;
declare const Popup: any;
declare const nsHttpClient: any;

type RuleLine = {
	id?: number;
	local_key?: string;
	account_id?: number | null;
	dynamic_account_role?: string | null;
	effect: 'increase' | 'decrease';
	amount_source: string;
	display_order?: number;
};

type RuleGroup = {
	id?: number;
	on: string;
	locked?: boolean;
	active?: boolean;
	lines: RuleLine[];
	saving?: boolean;
	error?: string;
	collapsed?: boolean;
};

export default {
	data() {
		return {
			loading: true,
			groups: [] as RuleGroup[],
			accounts: [] as any[],
			events: {} as Record<string, any>,
			searchQuery: ''
		};
	},
	computed: {
		filteredGroups(): RuleGroup[] {
			const query = this.searchQuery.trim().toLocaleLowerCase();

			if (!query) {
				return this.groups;
			}

			return this.groups.filter((group: RuleGroup) => {
				const event = this.events[group.on] || {};
				const searchable = [group.on, event.label, event.description, ...Object.values(event.amount_sources || {})]
					.filter(Boolean)
					.join(' ')
					.toLocaleLowerCase();

				return searchable.includes(query);
			});
		}
	},
	mounted() {
		forkJoin([nsHttpClient.get('/api/transactions-accounts'), nsHttpClient.get('/api/transactions/rules')]).subscribe({
			next: ([accounts, response]: [any[], any]) => {
				this.accounts = accounts;
				this.events = response.events;

				this.groups = Object.keys(this.events).map((event) => {
					const persisted = response.groups.find((group: RuleGroup) => group.on === event);

					return persisted
						? { ...persisted, collapsed: true }
						: {
							on: event,
							active: true,
							collapsed: false,
							lines: [this.newLine(event), this.newLine(event)]
						};
				});
				this.loading = false;
			},
			error: (error: any) => {
				this.loading = false;
				nsSnackBar.error(this.errorMessage(error));
			}
		});
	},
	methods: {
		__,
		newLine(event: string): RuleLine {
			return {
				local_key: Math.random().toString(36).slice(2),
				account_id: null,
				dynamic_account_role: null,
				effect: 'increase',
				amount_source: Object.keys(this.events[event]?.amount_sources || {})[0] || ''
			};
		},
		addLine(group: RuleGroup) {
			group.lines.push(this.newLine(group.on));
		},
		moveLine(group: RuleGroup, index: number, offset: number) {
			const target = index + offset;

			if (target < 0 || target >= group.lines.length) {
				return;
			}

			const [line] = group.lines.splice(index, 1);
			group.lines.splice(target, 0, line);
		},
		confirmRemoveLine(group: RuleGroup, line: RuleLine) {
			if (!line.id) {
				group.lines = group.lines.filter((candidate) => candidate !== line);
				return;
			}

			Popup.show(nsConfirmPopup, {
				title: __('Remove Accounting Action'),
				message: __('Remove this persisted action from the group? The change takes effect after you save the group.'),
				onAction: (confirmed: boolean) => {
					if (confirmed) {
						group.lines = group.lines.filter((candidate) => candidate !== line);
					}
				}
			});
		},
		confirmDeleteGroup(group: RuleGroup) {
			if (!group.id) {
				group.lines = [this.newLine(group.on), this.newLine(group.on)];
				return;
			}

			Popup.show(nsConfirmPopup, {
				title: __('Delete Accounting Rule Group'),
				message: __(
					'Delete this complete rule group? New activity for this event will not post until a valid group is saved.'
				),
				onAction: (confirmed: boolean) => {
					if (confirmed) {
						this.deleteGroup(group);
					}
				}
			});
		},
		deleteGroup(group: RuleGroup) {
			group.saving = true;
			nsHttpClient.delete(`/api/transactions/rules/${group.id}`).subscribe({
				next: () => {
					Object.assign(group, {
						id: undefined,
						lines: [this.newLine(group.on), this.newLine(group.on)],
						saving: false
					});
				},
				error: (error: any) => {
					group.saving = false;
					nsSnackBar.error(this.errorMessage(error));
				}
			});
		},
		saveGroup(group: RuleGroup) {
			group.error = '';

			if (group.lines.length < 2 || !this.isBalanced(group)) {
				group.error = __('Add at least two actions and balance every amount source before saving.');
				return;
			}

			group.saving = true;
			nsHttpClient
				.post('/api/transactions/rules', {
					rule: {
						id: group.id,
						on: group.on,
						active: true,
						lines: group.lines.map((line, index) => ({ ...line, display_order: index }))
					}
				})
				.subscribe({
					next: (response: any) => {
						Object.assign(group, response.data.rule, { saving: false, error: '' });
						nsSnackBar.success(__('The accounting rule group was saved.'));
					},
					error: (error: any) => {
						group.saving = false;
						group.error = this.errorMessage(error);
					}
				});
		},
		accountSelection(line: RuleLine): string {
			if (line.dynamic_account_role) {
				return `role:${line.dynamic_account_role}`;
			}

			return line.account_id ? `account:${line.account_id}` : '';
		},
		setAccountSelection(line: RuleLine, event: Event) {
			const selection = (event.target as HTMLSelectElement).value;
			const [type, value] = selection.split(':');
			line.account_id = type === 'account' ? Number(value) : null;
			line.dynamic_account_role = type === 'role' ? value : null;
		},
		sourceEntries(event: string): [string, string][] {
			return Object.entries(this.events[event]?.amount_sources || {});
		},
		roleEntries(event: string): [string, any][] {
			return Object.entries(this.events[event]?.dynamic_account_roles || {});
		},
		operation(line: RuleLine, event: string): 'debit' | 'credit' {
			if (line.dynamic_account_role) {
				return this.events[event]?.dynamic_account_roles?.[line.dynamic_account_role]?.operation || 'credit';
			}

			const account = this.accounts.find((candidate: any) => candidate.id === Number(line.account_id));
			const debitOnIncrease = ['assets', 'expenses'].includes(account?.category_identifier);
			const debit = line.effect === 'increase' ? debitOnIncrease : !debitOnIncrease;

			return debit ? 'debit' : 'credit';
		},
		operationFor(line: RuleLine, event: string): string {
			return this.operation(line, event) === 'debit' ? __('Posts as Debit') : __('Posts as Credit');
		},
		isBalanced(group: RuleGroup): boolean {
			if (
				group.lines.length < 2 ||
				group.lines.some((line) => !line.amount_source || (!line.account_id && !line.dynamic_account_role))
			) {
				return false;
			}

			const operations = group.lines.map((line) => ({
				source: line.amount_source,
				operation: this.operation(line, group.on) === 'debit' ? 'debit' : 'credit'
			}));

			if (group.on === 'order_finalized') {
				return (
					this.has(operations, 'debit', 'total') &&
					this.has(operations, 'credit', 'net_sale') &&
					this.has(operations, 'credit', 'tax') &&
					this.count(operations, 'debit', 'cogs') === this.count(operations, 'credit', 'cogs')
				);
			}

			if (group.on === 'order_refund') {
				return (
					this.has(operations, 'debit', 'net_refund') &&
					this.has(operations, 'debit', 'refunded_tax') &&
					this.has(operations, 'credit', 'refund_unpaid') &&
					this.has(operations, 'credit', 'refund_paid')
				);
			}

			return [...new Set(operations.map((line) => line.source))].every(
				(source) => this.count(operations, 'debit', source) === this.count(operations, 'credit', source)
			);
		},
		count(operations: any[], operation: string, source: string): number {
			return operations.filter((line) => line.operation === operation && line.source === source).length;
		},
		has(operations: any[], operation: string, source: string): boolean {
			return this.count(operations, operation, source) === 1;
		},
		balanceSummary(group: RuleGroup): string {
			const debits = group.lines.filter((line) => this.operation(line, group.on) === 'debit').length;
			const credits = group.lines.length - debits;

			return this.isBalanced(group)
				? __('Balanced  -  {debits} debit action(s), {credits} credit action(s)')
					.replace('{debits}', String(debits))
					.replace('{credits}', String(credits))
				: __('Unbalanced  -  review accounts and amount sources');
		},
		errorMessage(error: any): string {
			if (error?.errors) {
				return Object.values(error.errors).flat().join(' ');
			}

			return error?.message || __('Unable to complete the accounting request.');
		}
	}
};
</script>
