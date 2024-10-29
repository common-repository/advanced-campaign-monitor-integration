/* eslint-disable  react/no-unknown-property */
/* eslint-disable react-hooks/exhaustive-deps */
import {
	Section,
	Select,
	IconName,
	Icon,
	Button,
	ButtonColor,
	ButtonVariant,
	Container,
} from '@refactco/ui-kit';
import { useNavigate } from 'react-router-dom';
import { styled } from 'styled-components';
import Fields from './components/Fields';
import { logger } from '../../common/common-function';
import { toast } from 'react-toastify';
import Spinner from '../../components/spinner';
const { useState, useEffect } = wp.element;
const apiFetch = wp.apiFetch;

const AddRule = ( {
	setAddNewRule,
	editingRule,
	setReloadData,
	reloadData,
} ) => {
	const navigate = useNavigate();
	const isEditMode = !! editingRule; // Determine if we are in edit mode
	const title = isEditMode ? 'Edit Rule' : 'Add New Rule';
	const description = isEditMode
		? 'Edit your rule details below.'
		: 'Fill in the details to add a new rule.';
	const submitButtonLabel = isEditMode ? 'Edit Rule' : 'Add Rule';

	const [ isDisableFirstField, setIsDisableFirstField ] = useState( false );
	const [ rule, setRule ] = useState( {
		integration: '',
		trigger_object: '',
		trigger_condition: '',
		esp_name: 'campaign-monitor',
		list_id: '',
		status: 'active',
		tasks: [],
		triggers: [],
	} );

	const [ tasks, setTasks ] = useState( [] );
	const [ triggerTypes, setTriggerTypes ] = useState( [] );
	const [ lists, setLists ] = useState( [] );
	const [ loadingDate, setLoadingData ] = useState( false );

	// UseEffect for initial load and to handle editingRule changes
	useEffect( () => {
		// Function to fetch triggers and lists
		const fetchData = async () => {
			try {
				setLoadingData( true );
				const triggersResponse = await apiFetch( {
					path: '/re-esp/v1/campaign-monitor/triggers',
				} );
				setTriggerTypes( triggersResponse );

				const listsResponse = await apiFetch( {
					path: '/re-esp/v1/campaign-monitor/lists',
				} );
				setLists( listsResponse );

				if ( isEditMode && editingRule ) {
					// Make sure the triggers in the editingRule are also set correctly
					const matchingTriggerType = triggersResponse.find(
						( tr ) => tr.value === editingRule.integration
					);
					const updatedTriggers = matchingTriggerType
						? matchingTriggerType.triggers
						: [];

					setRule( {
						...editingRule,
						triggers: updatedTriggers,
					} );

					setTasks( editingRule.tasks );
				}
			} catch ( error ) {
				logger( error );
			} finally {
				setLoadingData( false );
			}
		};

		fetchData();
	}, [ editingRule ] ); // Depend on editingRule to re-run when it changes

	// This useEffect is used to update the tasks within the rule whenever tasks change
	useEffect( () => {
		setRule( ( prevRule ) => ( { ...prevRule, tasks } ) );
	}, [ tasks ] );

	const triggerTypeChangeHandler = ( value, { event } ) => {
		const dataType =
			event.target.selectedOptions[ 0 ].getAttribute( 'datatype' );
		setRule( ( prevRule ) => ( {
			...prevRule,
			integration: dataType,
			trigger_object: value,
			trigger_condition: '',
			triggers: triggerTypes.find(
				( triggerType ) => triggerType.value === dataType
			).triggers,
			list_id: '',
		} ) );
		setIsDisableFirstField( true );
	};

	const formSubmitHandler = ( e ) => {
		e.preventDefault();

		// all the rule.tasks properties should not be empty
		if ( rule.tasks.length > 0 ) {
			for ( let i = 0; i < rule.tasks.length; i++ ) {
				if ( rule.tasks[ i ].wp === '' || rule.tasks[ i ].esp === '' ) {
					toast.error( 'Please fill all the fields.' );
					return;
				}
			}
		} else {
			toast.error( 'Please fill all the fields.' );
			return;
		}

		const path = isEditMode
			? `/re-esp/v1/campaign-monitor/rules/${ editingRule.id }`
			: '/re-esp/v1/campaign-monitor/rules';
		const method = isEditMode ? 'PUT' : 'POST';

		apiFetch( { path, method, data: rule } )
			.then( () => {
				if ( isEditMode ) {
					toast.success( 'Rule updated successfully' );
					setAddNewRule( false );
					setReloadData( ! reloadData );
				} else {
					toast.success( 'Rule added successfully' );
				}
				navigate( '/sync-rules/sync' );
			} )
			.catch( ( error ) => {
				logger( error );
				toast.error( error.message );
			} );
	};

	const handleCancel = () => {
		if ( isEditMode ) {
			setAddNewRule( false );
		} else {
			navigate( '/sync-rules/sync' );
		}
	};

	return (
		<Section
			headerProps={ {
				title,
				description,
			} }
		>
			{ loadingDate ? (
				<Spinner />
			) : (
				<>
					<Container>
						<Select
							label="Trigger Type"
							onChange={ triggerTypeChangeHandler }
							value={ rule.trigger_object }
						>
							<option
								value="0"
								selected={ true }
								disabled={ isDisableFirstField }
							>
								Select a Trigger Type
							</option>
							{ triggerTypes.map( ( triggerType, index ) => (
								<>
									{ triggerType.disabled === false && (
										<option
											datatype={ triggerType.value }
											value={ triggerType.value }
										>
											{ triggerType.label }
										</option>
									) }
									{ triggerType.children.length > 0 && (
										<optgroup
											label={ triggerType.label }
											key={ index }
										>
											{ triggerType.children.map(
												( child, i ) => (
													<option
														key={ i }
														datatype={
															triggerType.value
														}
														value={ child.value }
													>
														{ child.label }
													</option>
												)
											) }
										</optgroup>
									) }
								</>
							) ) }
						</Select>
						{ rule.trigger_object !== '' &&
							rule.triggers?.length > 0 && (
								<Select
									onChange={ ( e ) =>
										setRule( {
											...rule,
											trigger_condition: e,
										} )
									}
									value={ rule.trigger_condition }
								>
									<option
										value="0"
										key={ -1 }
										selected={ true }
										disabled={
											rule.trigger_condition !== '' &&
											rule.trigger_condition !== '0'
										}
									>
										Select a Trigger Value
									</option>
									{ rule.triggers.map( ( child, index ) => (
										<option
											key={ index }
											value={ child.value }
											selected={
												rule.trigger_condition ===
												child.value
											}
										>
											{ child.label }
										</option>
									) ) }
								</Select>
							) }

						{ lists.length > 0 &&
							rule.trigger_condition !== '' &&
							rule.trigger_condition !== '0' && (
								<Select
									label={ 'List' }
									onChange={ ( e ) =>
										setRule( { ...rule, list_id: e } )
									}
									value={ rule.list_id }
								>
									<option
										value="0"
										key={ -1 }
										selected={ true }
										disabled={ rule.list_id !== '' }
									>
										Select a list
									</option>
									{ lists.map( ( list, index ) => (
										<option
											key={ index }
											value={ list.value }
										>
											{ list.label }
										</option>
									) ) }
								</Select>
							) }

						{ rule.list_id.length > 0 && (
							<StyledContainer>
								<Fields tasks={ tasks } setTasks={ setTasks } />
							</StyledContainer>
						) }
					</Container>
					<ButtonContainer>
						<Button
							color={ ButtonColor.GREEN }
							variant={ ButtonVariant.PRIMARY }
							onClick={ formSubmitHandler }
							disabled={
								rule.tasks.length === 0 ||
								rule.list_id.length === 0
							}
							icon={
								<Icon
									iconName={
										isEditMode
											? IconName.DONE
											: IconName.PLUS
									}
								/>
							}
						>
							{ submitButtonLabel }
						</Button>

						<Button
							color={ ButtonColor.RED }
							onClick={ handleCancel }
							variant={ ButtonVariant.response }
							icon={ <Icon iconName={ IconName.DELETE } /> }
						>
							Cancel
						</Button>
					</ButtonContainer>
				</>
			) }
		</Section>
	);
};

const ButtonContainer = styled.div`
	display: flex;
	justify-content: flex-start;
	margin-top: 20px;
	gap: 1rem;
`;

// styled components for Container
const StyledContainer = styled.div`
	max-width: 80%;
	margin: 1rem auto;
	background: #f5f5f5;
	padding: 1rem;
	border-radius: 0.5rem;
`;
export default AddRule;
