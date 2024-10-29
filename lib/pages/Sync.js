/* eslint-disable no-nested-ternary */
import {
	Section,
	Table,
	Button,
	ButtonColor,
	Icon,
	IconName,
} from '@refactco/ui-kit';
import AddRule from './Sync/AddRule';
const apiFetch = wp.apiFetch;
const { useState, useEffect } = wp.element;
import { useNavigate } from 'react-router-dom';
import { logger } from '../common/common-function';
import Modal from '../components/modal';
import styled from 'styled-components';
import { toast } from 'react-toastify';
import Spinner from '../components/spinner';

const Sync = () => {
	const navigate = useNavigate();
	const [ addNewRule, setAddNewRule ] = useState( false );
	const [ editingRule, setEditingRule ] = useState( null );
	const [ rules, setRules ] = useState( [] );
	const [ dataRows, setDataRows ] = useState( [] );
	const [ showModal, setShowModal ] = useState( false );
	const [ selectedRule, setSelectedRule ] = useState( null );
	const [ loadingDeleteAction, setLoadingDeleteAction ] = useState( false );
	const [ loadingData, setLoadingData ] = useState( false );
	const [ areSettingsSaved, setAreSettingsSaved ] = useState( true );
	const [ reloadData, setReloadData ] = useState( false );

	const fetchSettings = async () => {
		try {
			const result = await apiFetch( {
				path: '/re-esp-campaign-monitor/v1/get-settings',
			} );
			if ( ! result.api_key || ! result.client_id ) {
				setAreSettingsSaved( false );
			}
		} catch ( err ) {
			logger( 'Error fetching settings. Please try again.' );
		}
	};

	useEffect( () => {
		fetchSettings();
	}, [] );

	useEffect( () => {
		setLoadingData( true );
		// get rules
		apiFetch( {
			path: '/re-esp/v1/campaign-monitor/rules',
		} )
			.then( ( response ) => {
				setRules( response );
			} )
			.catch( ( error ) => {
				logger( error );
				toast.error( error );
			} )
			.finally( () => {
				setLoadingData( false );
			} );
	}, [ reloadData ] );

	useEffect( () => {
		const rows = rules.map( ( row ) => {
			return [
				row.integration,
				row.trigger_object,
				row.trigger_condition,
				row.list_id,
				row.status,
			];
		} );
		setDataRows( rows );
	}, [ rules ] );

	const editRuleHandler = ( index ) => {
		const ruleToEdit = rules[ index ];
		setEditingRule( ruleToEdit ); // Set the current rule to be edited
		setAddNewRule( true ); // Optionally, use this to toggle to the AddRule view
	};

	const actions = [
		{
			text: 'Edit',
			onClick: ( index ) => {
				editRuleHandler( index );
			},
		},
		{
			color: ButtonColor.RED,
			text: 'Delete',
			onClick: ( index ) => {
				const rule = rules[ index ];
				setSelectedRule( rule );
				setShowModal( true );
			},
		},
	];

	const modalCloseHandler = () => {
		setShowModal( false );
	};

	const deleteRuleHandler = async () => {
		if ( selectedRule ) {
			try {
				setLoadingDeleteAction( true );
				await apiFetch( {
					path: `/re-esp/v1/campaign-monitor/rules/${ selectedRule.id }`,
					method: 'DELETE',
				} );

				const newRules = rules.filter(
					( r ) => r.id !== selectedRule.id
				);
				setRules( newRules );
				setLoadingDeleteAction( false );
				setSelectedRule( null );
				setShowModal( false );
				toast.success( 'The Rule deleted successfully.' );
			} catch ( error ) {
				logger( error );
			}
		}
	};

	const headers = [
		'Integration',
		'Trigger Object',
		'Trigger Condition',
		'List',
		'Status',
	];

	return areSettingsSaved === false ? (
		<Section
			headerProps={ {
				title: 'Sync Rules',
				description: 'Sync your data with Campaign Monitor',
			} }
		>
			<Icon iconName={ IconName.WARNING_OUTLINED } />
			API key and Client ID are not set. Please set them in the setting
			tab.
		</Section>
	) : addNewRule ? (
		<AddRule
			setAddNewRule={ setAddNewRule }
			editingRule={ editingRule }
			key={ editingRule ? editingRule.id : 'new-rule' }
			setReloadData={ setReloadData }
			reloadData={ reloadData }
		/>
	) : (
		<Section
			headerProps={ {
				title: 'Sync Rules',
				description: 'Sync your data with Campaign Monitor',
			} }
		>
			<Button
				text="Add New Rule"
				onClick={ () => navigate( '/sync-rules/add-new' ) }
				style={ { margin: '20px 0' } }
			/>
			{ loadingData ? (
				<Spinner />
			) : (
				<Table
					headers={ headers }
					actions={ actions }
					dataRows={ dataRows }
					noDraggable
				/>
			) }

			<Modal show={ showModal } modalClosed={ modalCloseHandler }>
				<h3>Are you sure you want to delete this rule?</h3>
				<b>Rule Details:</b>
				<ul>
					<li>
						<b>Integration:</b> { selectedRule?.integration }
					</li>
					<li>
						<b>Trigger Object:</b> { selectedRule?.trigger_object }
					</li>
					<li>
						<b>Trigger Condition:</b>{ ' ' }
						{ selectedRule?.trigger_condition }
					</li>
				</ul>
				<ButtonContainer>
					<Button
						color={ ButtonColor.RED }
						onClick={ deleteRuleHandler }
						disabled={ loadingDeleteAction }
					>
						Delete Rule
					</Button>
					<Button
						onClick={ modalCloseHandler }
						disabled={ loadingDeleteAction }
					>
						Cancel
					</Button>
				</ButtonContainer>
			</Modal>
		</Section>
	);
};

export const ButtonContainer = styled.div`
	display: flex;
	gap: 16px;
	margin-top: 20px;
`;

export default Sync;
