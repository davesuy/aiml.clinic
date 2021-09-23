<div>
	<div class="jet-apb-working-hours__heading">
		<h4 class="cx-vui-subtitle"><?php esc_html_e( 'Booking Schedule', 'jet-appointments-booking' ); ?></h4>
	</div>
	<cx-vui-time
		class="jet-apb-working-hours__main-settings"
		label="<?php esc_html_e( 'Slot Duration', 'jet-appointments-booking' ); ?>"
		description="<?php esc_html_e( 'Select the default duration for each service and provider time slot', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		placeholder="00:01"
		:value="getTimeSettings( 'default_slot' )"
		format="HH:mm"
		@input="onUpdateTimeSettings( {
			key: 'default_slot',
			value: $event,
		} )"
	></cx-vui-time>
	<cx-vui-time
		class="jet-apb-working-hours__main-settings"
		label="<?php esc_html_e( 'Buffer Time Before Slot', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		placeholder="00:00"
		:value="getTimeSettings( 'default_buffer_before' )"
		format="HH:mm"
		@input="onUpdateTimeSettings( {
			key: 'default_buffer_before',
			value: $event,
		} )"
	></cx-vui-time>
	<cx-vui-time
		class="jet-apb-working-hours__main-settings"
		label="<?php esc_html_e( 'Buffer Time After Slot', 'jet-appointments-booking' ); ?>"
		:wrapper-css="[ 'equalwidth' ]"
		:size="'fullwidth'"
		placeholder="00:00"
		:value="getTimeSettings( 'default_buffer_after' )"
		format="HH:mm"
		@input="onUpdateTimeSettings( {
			key: 'default_buffer_after',
			value: $event,
		} )"
	></cx-vui-time>
	<div class="jet-apb-working-hours">
		<div class="jet-apb-week-days jet-apb-working-hours__columns">
			<div class="jet-apb-working-hours__heading">
				<h4 class="cx-vui-subtitle"><?php esc_html_e( 'Work Hours', 'jet-appointments-booking' ); ?></h4>
			</div>
			<div class="jet-apb-week-day" v-for="( label, day ) in settings.weekdays" :key="day">
				<div class="jet-apb-week-day__head">
					<div class="jet-apb-week-day__head-name">{{ label }}</div>
					<div class="jet-apb-week-day__head-actions">
						<cx-vui-button
							size="mini"
							button-style="accent"
							@click="newSlot( day )"
						>
							<span slot="label"><?php esc_html_e( '+ Add', 'jet-appointments-booking' ); ?></span>
						</cx-vui-button>
					</div>
				</div>
				<div class="jet-apb-week-day__body">
					<div
						class="jet-apb-week-day__slot"
						v-for="( daySlot, slotIndex ) in settings.working_hours[ day ]"
					>
						<div class="jet-apb-week-day__slot-name">
							{{ daySlot.from }}-{{ daySlot.to }}
						</div>
						<div class="jet-apb-working-hours__slot-actions">
							<span
								class="dashicons dashicons-edit"
								@click="editSlot( day, slotIndex, daySlot )"
							></span>
							<div class="jet-apb-week-day__slot-delete" style="position:relative;">
								<span
									class="dashicons dashicons-trash"
									@click="confirmDeleteSlot( day, slotIndex )"
								></span>
								<div
									class="cx-vui-tooltip"
									v-if="deleteSlotTrigger === day + '-' + slotIndex"
								>
									<?php esc_html_e( 'Are you sure?', 'jet-appointments-booking' ); ?>
									<br> <span
										class="cx-vui-repeater-item__confrim-del"
										@click="deleteSlot( day, slotIndex, daySlot )"
									><?php
										esc_html_e( 'Yes', 'jet-appointments-booking' );
									?></span>
									/
									<span
										class="cx-vui-repeater-item__cancel-del"
										@click="deleteSlotTrigger = null"
									><?php
										esc_html_e( 'No', 'jet-appointments-booking' );
									?></span></div>
								</div>
							</div>
					</div>
				</div>
			</div>
		</div>

		<div class="jet-apb-days-schedule jet-apb-working-hours__columns">
			<cx-vui-collapse
				:collapsed="false"
			>
				<h4 class="cx-vui-subtitle"  slot="title"><?php esc_html_e( 'Days Off', 'jet-appointments-booking' ); ?></h4>
				<div class="cx-vui-panel" slot="content">
					<div class="jet-apb-working-hours__heading">
						<div class="cx-vui-component__desc"><?php esc_html_e( 'Set the days that will be the weekend.', 'jet-appointments-booking' ); ?></div>
						<cx-vui-button
							size="mini"
							button-style="accent"
							@click="showEditDay( 'days_off' )"
						>
							<span slot="label"><?php esc_html_e( 'Add Days', 'jet-appointments-booking' ); ?></span>
						</cx-vui-button>
					</div>

					<div class="jet-apb-working-hours__body">
						<div
							class="jet-apb-days-schedule__slot"
							v-for="(offDate, key) in settings.days_off"
						>
							<div>
								{{ offDate.start }} — {{ offDate.end }} {{ offDate.name }}
							</div>
							<div class="jet-apb-working-hours__slot-actions">
								<span
									class="dashicons dashicons-edit"
									@click="showEditDay( 'days_off', offDate )"
								></span>
								<div style="position:relative;">
									<span
										class="dashicons dashicons-trash"
										@click="confirmDeleteDay( offDate )"
									></span>
									<div
										class="cx-vui-tooltip"
										v-if="deleteDayTrigger === offDate"
									>
										<?php esc_html_e( 'Are you sure?', 'jet-appointments-booking' ); ?>
										<br><span
											class="cx-vui-repeater-item__confrim-del"
											@click="deleteDay( 'days_off', offDate )"
										><?php
											esc_html_e( 'Yes', 'jet-appointments-booking' );
										?></span>
										/
										<span
											class="cx-vui-repeater-item__cancel-del"
											@click="deleteDayTrigger = null"
										><?php
											esc_html_e( 'No', 'jet-appointments-booking' );
										?></span></div>
									</div>
								</div>
						</div>
					</div>
				</div>
			</cx-vui-collapse>

			<cx-vui-collapse
				:collapsed="false"
			>
				<h4 class="cx-vui-subtitle"  slot="title"><?php esc_html_e( 'Working Days', 'jet-appointments-booking' ); ?></h4>
				<div class="cx-vui-panel" slot="content">
					<div class="jet-apb-working-hours__heading">
						<div class="cx-vui-component__desc"><?php esc_html_e( 'Set available days for booking.', 'jet-appointments-booking' ); ?></div>
						<cx-vui-button
							size="mini"
							button-style="accent"
							@click="showEditDay( 'working_days' )"
						>
							<span slot="label"><?php esc_html_e( 'Add Days', 'jet-appointments-booking' ); ?></span>
						</cx-vui-button>
					</div>

					<div class="jet-apb-working-hours__body">
						<div
							class="jet-apb-days-schedule__slot"
							v-for="(workingDate, key) in settings.working_days"
						>
							<div>
								{{ workingDate.start }} — {{ workingDate.end }} {{ workingDate.name }}
							</div>
							<div class="jet-apb-working-hours__slot-actions">
								<span
									class="dashicons dashicons-edit"
									@click="showEditDay( 'working_days', workingDate )"
								></span>
								<div style="position:relative;">
									<span
										class="dashicons dashicons-trash"
										@click="confirmDeleteDay( workingDate )"
									></span>
									<div
										class="cx-vui-tooltip"
										v-if="deleteDayTrigger === workingDate"
									>
										<?php esc_html_e( 'Are you sure?', 'jet-appointments-booking' ); ?>
										<br><span
											class="cx-vui-repeater-item__confrim-del"
											@click="deleteDay( 'working_days', workingDate )"
										><?php
											esc_html_e( 'Yes', 'jet-appointments-booking' );
										?></span>
										/
										<span
											class="cx-vui-repeater-item__cancel-del"
											@click="deleteDayTrigger = null"
										><?php
											esc_html_e( 'No', 'jet-appointments-booking' );
										?></span></div>
									</div>
								</div>
						</div>
					</div>
				</div>
			</cx-vui-collapse>
		</div>

		<cx-vui-popup
			v-model="isNewSlot"
			body-width="600px"
			ok-label="<?php esc_html_e( 'Save', 'jet-appointments-booking' ) ?>"
			cancel-label="<?php esc_html_e( 'Cancel', 'jet-appointments-booking' ) ?>"
			@on-cancel="handleCancel"
			@on-ok="handleOk"
		>
			<div class="cx-vui-subtitle" slot="title"><?php
				esc_html_e( 'Work Hours', 'jet-appointments-booking' );
			?></div>
			<cx-vui-time
				format="HH:mm"
				slot="content"
				label="<?php esc_html_e( 'From', 'jet-appointments-booking' ); ?>"
				description="<?php esc_html_e( 'Starts from time', 'jet-appointments-booking' ); ?>"
				size="fullwidth"
				:wrapper-css="[ 'equalwidth' ]"
				:value="getSlotTime( 'currentFrom' )"
				@input="setTimeSettings( {
					key: 'currentFrom',
					value: $event,
				} )"
			></cx-vui-time>

			<cx-vui-time
				format="HH:mm"
				slot="content"
				label="<?php esc_html_e( 'To', 'jet-appointments-booking' ); ?>"
				description="<?php esc_html_e( 'Work to time', 'jet-appointments-booking' ); ?>"
				size="fullwidth"
				:wrapper-css="[ 'equalwidth' ]"
				:value="getSlotTime( 'currentTo' )"
				@input="setTimeSettings( {
					key: 'currentTo',
					value: $event,
				} )"
			></cx-vui-time>
		</cx-vui-popup>

		<cx-vui-popup
			v-model="editDay"
			body-width="600px"
			ok-label="<?php esc_html_e( 'Save', 'jet-appointments-booking' ) ?>"
			cancel-label="<?php esc_html_e( 'Cancel', 'jet-appointments-booking' ) ?>"
			@on-cancel="handleDayCancel"
			@on-ok="handleDayOk"
		>
			<div class="cx-vui-subtitle" slot="title"><?php
				esc_html_e( 'Select Days', 'jet-appointments-booking' );
			?></div>
			<cx-vui-input
				label="<?php esc_html_e( 'Days Label', 'jet-appointments-booking' ); ?>"
				description="<?php esc_html_e( 'Name of the current day (eg. name of the holiday)', 'jet-appointments-booking' ); ?>"
				:wrapper-css="[ 'equalwidth' ]"
				size="fullwidth"
				v-model="date.name"
				slot="content"
			></cx-vui-input>

			<cx-vui-component-wrapper
				:wrapper-css="[ 'equalwidth' ]"
				label="<?php esc_html_e( 'Start Date *', 'jet-appointments-booking' ); ?>"
				description="<?php esc_html_e( 'Pick a start day', 'jet-appointments-booking' ); ?>"
				slot="content"
			>
				<vuejs-datepicker
					input-class="cx-vui-input size-fullwidth"
					placeholder="<?php esc_html_e( 'Select Date', 'jet-appointments-booking' ); ?>"
					:format="formatDate"
					:disabled-dates="disabledDate"
					v-model="date.start"
				></vuejs-datepicker>
			</cx-vui-component-wrapper>

			<cx-vui-component-wrapper
				:wrapper-css="[ 'equalwidth' ]"
				label="<?php esc_html_e( 'End Date', 'jet-appointments-booking' ); ?>"
				description="<?php esc_html_e( 'Pick a end day', 'jet-appointments-booking' ); ?>"
				slot="content"
			>
				<vuejs-datepicker
					input-class="cx-vui-input size-fullwidth"
					placeholder="<?php esc_html_e( 'Select Date', 'jet-appointments-booking' ); ?>"
					:format="formatDate"
					:disabled-dates="disabledDate"
					v-model="date.end"
				></vuejs-datepicker>
			</cx-vui-component-wrapper>
		</cx-vui-popup>
	</div>
</div>
