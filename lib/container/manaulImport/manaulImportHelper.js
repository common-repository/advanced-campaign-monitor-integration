/* eslint-disable camelcase */

const apiFetch = wp.apiFetch;
import { toast } from 'react-toastify';
import { logger, delay } from '../../common/common-function';
export function manaulImportHelper( state, setState ) {
	async function getDefaultOptions() {
		try {
			const response = await apiFetch( {
				path: 're-esp-campaign-monitor/v1/import-defaults-options',
			} );
			const { post_statuses, authors, current_server_time, post_types } =
				response;

			if ( authors.length === 0 ) {
				toast.error(
					'You should add at least one new user with rule Author.Navigating to the Add New User page...'
				);
				await delay( 5000 );
				window.location.href = 'user-new.php';
				return false;
			}

			const postStatusesData = Object.entries( post_statuses ).map(
				( [ key, value ] ) => ( {
					label: value,
					value: key,
				} )
			);
			const selectedPostType = post_types[ 0 ].post_type;
			const taxonomies = post_types[ 0 ].taxonomies
				? post_types[ 0 ].taxonomies
				: [];
			const selectedTaxonomy =
				taxonomies.length > 0 ? taxonomies[ 0 ].taxonomy_slug : '';
			const terms = taxonomies[ 0 ].terms ? taxonomies[ 0 ].terms : [];
			const selectedTerm = terms.length > 0 ? terms[ 0 ].term_id : '';
			const selectedAuthor = authors[ 0 ].id;

			setState( {
				...state,
				postStatuses: postStatusesData,
				postTypes: post_types,
				taxonomies,
				selectedTaxonomy,
				selectedPostType: selectedPostType ? selectedPostType : 'post',
				terms,
				selectedTerm,
				authors,
				selectedAuthor,
				serverTime: current_server_time,
			} );
		} catch ( error ) {
			logger( error );
		}
	}

	function handleInputChange( value, name ) {
		setState( ( prevState ) => {
			const newState = { ...prevState, [ name ]: value };

			const {
				publishedCampaigns,
				draftededCampaigns,
				scheduledCampaigns,
			} = newState;

			if ( name === 'selectedPostType' ) {
				const selectedPostType = state.postTypes.find(
					( pt ) => pt.post_type === value
				);
				newState.taxonomies = selectedPostType
					? selectedPostType.taxonomies
					: [];
			}

			if ( name === 'selectedTaxonomy' ) {
				const selectedTaxonomy = state.taxonomies.find(
					( pt ) => pt.taxonomy_slug === value
				);
				newState.terms = selectedTaxonomy ? selectedTaxonomy.terms : [];
			}
			if (
				( name === 'publishedCampaigns' ||
					name === 'draftededCampaigns' ||
					name === 'scheduledCampaigns' ) &&
				value
			) {
				newState.errors = { ...prevState.errors, campaignStatus: '' };
				newState.isFormDisabled = false;
			}
			if (
				! publishedCampaigns &&
				! draftededCampaigns &&
				! scheduledCampaigns
			) {
				newState.errors = {
					...prevState.errors,
					campaignStatus:
						'You should select at least one campaign status',
				};
				newState.isFormDisabled = true;
			}

			return newState;
		} );
	}

	function validateInput( name, value ) {
		switch ( name ) {
			case 'apiKey':
				if ( ! value ) return 'API Key is required.';
				if ( value.length !== 152 )
					return 'API Key must be 152 characters.';
				break;
			case 'clientId':
				if ( ! value ) return 'Client ID is required.';
				if ( value.length !== 32 )
					return 'Client ID must be 32 characters.';
				break;
			case 'runHour':
				if ( ! value ) return 'Run hour is required.';
				if ( value < 1 || value > 24 )
					return 'The run hour should be between 1 to 24';
				if ( ! /^(?:[01]?[0-9]|2[0-4])$/.test( value ) )
					return 'Invalid hour format';
				break;
			case 'runTime':
				if ( ! value ) return 'Run time is required.';
				if ( ! /^(?:[01]\d|2[0-3]):[0-5]\d$/.test( value ) )
					return 'Invalid time format. Use "HH:mm" (e.g., "02:00").';
				break;
			default:
				return '';
		}
	}

	function handleBlur( name, value ) {
		const error = validateInput( name, value );
		if ( error ) {
			setState( ( prevState ) => ( {
				...prevState,
				errors: { ...prevState.errors, [ name ]: error },
				isFormDisabled: true,
			} ) );
		} else {
			setState( ( prevState ) => ( {
				...prevState,
				errors: { ...prevState.errors, [ name ]: '' },
				isFormDisabled: false,
			} ) );
		}
	}

	async function getImportStatus( import_id ) {
		let response = null;
		try {
			const payload = {
				path: 're-esp-campaign-monitor/v1/import-status',
				method: 'POST',
			};
			if ( import_id ) {
				payload.data = { import_id };
			}

			response = await apiFetch( payload );
		} catch ( error ) {
			logger( 'ImportStatus request error:', error );
		}

		return response;
	}

	function updateProgress( response, intervalId ) {
		const { total_campaigns, remaining_campaigns, status } = response;
		const totalCampaignsNumber = Number( total_campaigns );
		const progressValue =
			( ( totalCampaignsNumber - remaining_campaigns ) /
				totalCampaignsNumber ) *
			100;
		setState( ( prevState ) => ( {
			...prevState,
			progressValue: Math.floor( progressValue ),
			startImporting: true,
		} ) );

		if ( intervalId && status !== 'active' ) {
			clearAllIntervals();
			setState( ( prevState ) => {
				return {
					...prevState,
					intervalId: [],
					progressValue: 100,
				};
			} );
		}
	}

	const clearAllIntervals = () => {
		const { intervalId } = state;
		intervalId.forEach( ( id ) => clearInterval( id ) );
		setState( ( prevState ) => ( {
			...prevState,
			intervalId: [],
		} ) );
	};

	async function importStatus( import_id ) {
		const intervalId = setInterval( async () => {
			const response = import_id
				? await getImportStatus( import_id )
				: await getImportStatus();

			if ( response && response.status === 'active' ) {
				updateProgress( response, intervalId );
			}
			if ( response && response.status === 'not_active' ) {
				clearAllIntervals();
				toast.success( 'Import completed successfully' );
				setState( ( prevState ) => {
					return {
						...prevState,
						progressValue: 100,
						loadingJobAction: true,
					};
				} );
				await delay( 5000 );
				window.location.reload();
			}
		}, 10000 );

		return intervalId;
	}

	async function checkCurrentImportStatus() {
		const response = await getImportStatus();
		if ( response && response.status === 'active' ) {
			updateProgress( response );
			clearAllIntervals();
			const newIntervalId = importStatus();
			setState( ( prevState ) => ( {
				...prevState,
				intervalId: [ ...state.intervalId, newIntervalId ],
			} ) );
		}
		if ( response && response.status === 'paused' ) {
			updateProgress( response );
			setState( ( prevState ) => {
				return {
					...prevState,
					jobAction: 'resume',
				};
			} );
		}
		setState( ( prevState ) => ( {
			...prevState,
			loading: false,
		} ) );
	}

	async function startImportHandler( event ) {
		event.preventDefault();
		const {
			apiKey,
			clientId,
			publishedCampaigns,
			publishedWrodpressPostStatus,
			draftededCampaigns,
			draftedWrodpressPostStatus,
			scheduledCampaigns,
			scheduledWrodpressPostStatus,
			selectedPostType,
			selectedTaxonomy,
			selectedTerm,
			selectedAuthor,
			importCampaignTagAs,
			importOption,
			enableSchedule,
			frequency,
			runHour,
			runTime,
			runDay,
		} = state;
		const post_status = {};
		if ( publishedCampaigns ) {
			post_status.published = publishedWrodpressPostStatus;
		}
		if ( draftededCampaigns ) {
			post_status.draft = draftedWrodpressPostStatus;
		}
		if ( scheduledCampaigns ) {
			post_status.scheduled = scheduledWrodpressPostStatus;
		}
		const schedule_settings = {};
		if ( ! enableSchedule ) {
			schedule_settings.enabled = 'off';
		}
		if ( enableSchedule ) {
			schedule_settings.enabled = 'on';
			schedule_settings.frequency = frequency;
			if ( frequency === 'hourly' ) {
				schedule_settings.specific_hour = runHour;
			} else if ( frequency === 'daily' ) {
				schedule_settings.time = runTime;
			} else {
				schedule_settings.time = runTime;
				schedule_settings.specific_day = runDay;
			}
		}

		const data = {
			credentials: JSON.stringify( {
				api_key: apiKey,
				client_id: clientId,
			} ),
			post_status: JSON.stringify( post_status ),
			post_type: selectedPostType,
			taxonomy: selectedTaxonomy ?? null,
			taxonomy_term: selectedTerm ?? null,
			author: selectedAuthor ?? null,
			import_cm_tags_as: importCampaignTagAs ?? null,
			import_option: importOption ?? null,
			schedule_settings: JSON.stringify( schedule_settings ),
		};

		try {
			setState( ( prevState ) => ( {
				...prevState,
				isFormDisabled: true,
				disableInput: true,
			} ) );

			const response = await apiFetch( {
				path: 're-esp-campaign-monitor/v1/import-campaigns',
				method: 'POST',
				data,
			} );

			const { import_id, message } = response;
			let scheduleId = null;
			if ( response.schedule_id ) {
				scheduleId = response.schedule_id;
			}

			setState( ( prevState ) => ( {
				...prevState,
				errors: { ...prevState.errors, requestError: '' },
				startImporting: true,
				scheduleId,
			} ) );

			toast.success( message );

			const newIntervalId = importStatus( import_id );

			setState( ( prevState ) => ( {
				...prevState,
				intervalId: [ ...state.intervalId, newIntervalId ],
				disableInput: false,
			} ) );
		} catch ( error ) {
			const { message } = error;

			setState( ( prevState ) => ( {
				...prevState,
				errors: { ...prevState.errors, requestError: message },
				isFormDisabled: false,
				disableInput: false,
			} ) );

			toast.error( message );
		}
	}

	async function jobActionHandler( jobAction ) {
		setState( ( prevState ) => {
			return {
				...prevState,
				loadingJobAction: true,
			};
		} );
		try {
			clearAllIntervals();
			const response = await apiFetch( {
				path: `re-esp-campaign-monitor/v1/manage-import-job?job_action=${ jobAction }`,
				method: 'POST',
			} );
			if ( response ) {
				const { message } = response;
				if ( jobAction === 'cancel' ) {
					toast.warning( message );
					setState( ( prevState ) => {
						return {
							...prevState,
							progressValue: 0,
							startImporting: false,
							intervalId: [],
							loadingJobAction: false,
						};
					} );
				}
				if ( jobAction === 'pause' ) {
					toast.warning( message );
					setState( ( prevState ) => {
						return {
							...prevState,
							jobAction: 'resume',
							intervalId: [],
						};
					} );
				}
				if ( jobAction === 'resume' ) {
					toast.success( message );
					setState( ( prevState ) => ( {
						...prevState,
						jobAction: 'pause',
						loadingJobAction: false,
					} ) );
					const newIntervalId = await importStatus();
					setState( ( prevState ) => ( {
						...prevState,
						intervalId: [ ...state.intervalId, newIntervalId ],
					} ) );
				}
			}
		} catch ( error ) {
			const { message } = error;
			toast.error( message );
		} finally {
			setState( ( prevState ) => {
				return {
					...prevState,
					loadingJobAction: false,
				};
			} );
		}
	}

	return {
		getDefaultOptions,
		handleInputChange,
		handleBlur,
		startImportHandler,
		checkCurrentImportStatus,
		jobActionHandler,
	};
}
