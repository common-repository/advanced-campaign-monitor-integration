/* eslint-disable react-hooks/exhaustive-deps */
const { useEffect, useState } = wp.element;
const apiFetch = wp.apiFetch;
import {
	Section,
	Input,
	InputType,
	Button,
	ButtonSize,
	ButtonColor,
	Icon,
	IconName,
	IconSize,
	Alert,
	AlertStatus,
} from '@refactco/ui-kit';
import { styled } from 'styled-components';

const Setting = () => {
	const [ settings, setSettings ] = useState( {
		api_key: '',
		client_id: '',
	} );
	const [ areSettingsSaved, setAreSettingsSaved ] = useState( false );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ isDisconnecting, setIsDisconnecting ] = useState( false );
	const [ errorMessage, setErrorMessage ] = useState( '' );
	const [ successMessage, setSuccessMessage ] = useState( '' );

	const setMessage = ( error = '', success = '' ) => {
		setErrorMessage( error );
		setSuccessMessage( success );
	};

	const fetchSettings = async () => {
		try {
			const result = await apiFetch( {
				path: '/re-esp-campaign-monitor/v1/get-settings',
			} );
			if ( result.api_key && result.client_id ) {
				setSettings( result );
				setAreSettingsSaved( true );
			}
		} catch ( err ) {
			setMessage( 'Error fetching settings. Please try again.' );
		}
	};

	const saveSettings = async ( e ) => {
		e.preventDefault();
		setIsSaving( true );
		if ( ! settings.api_key || ! settings.client_id ) {
			setMessage( 'Please enter both API Key and Clint ID.' );
			setIsSaving( false );
			return;
		}
		try {
			const result = await apiFetch( {
				path: '/re-esp-campaign-monitor/v1/save-settings',
				method: 'POST',
				data: settings,
			} );
			setSettings( result );
			setAreSettingsSaved( true );
			setMessage( '', 'Settings saved successfully.' );
		} catch ( err ) {
			//check if error code is 401
			if ( err.code === 'api_error' ) {
				setMessage( err.message );
			} else {
				setMessage( 'Error saving settings. Please try again.' );
			}
			setAreSettingsSaved( false );
		} finally {
			setIsSaving( false );
		}
	};

	const deleteSettings = async ( e ) => {
		e.preventDefault();
		setIsDisconnecting( true );
		try {
			await apiFetch( {
				path: '/re-esp-campaign-monitor/v1/delete-settings',
				method: 'DELETE',
			} );
			setSettings( { api_key: '', client_id: '' } );
			setAreSettingsSaved( false );
			setMessage(
				'',
				'Successfully disconnected from Campaign Monitor.'
			);
		} catch ( err ) {
			setMessage( 'Error disconnecting. Please try again.' );
		} finally {
			setIsDisconnecting( false );
		}
	};

	const handleInputChange = ( name, value ) => {
		setSettings( ( prevSettings ) => ( {
			...prevSettings,
			[ name ]: value,
		} ) );
	};

	useEffect( () => {
		fetchSettings();
	}, [] );

	return (
		<>
			<Section
				headerProps={ {
					title: 'API Settings',
					description: 'Enter your API settings below.',
					infoText: 'Click your Campaign Monitor profile image at the top right, then select Account settings, Then Click API keys to view the page that holds your API key and client ID.'
				} }
			>
				{ errorMessage && (
					<StyledAlert status={ AlertStatus.ERROR }>
						{ errorMessage }
					</StyledAlert>
				) }
				{ successMessage && (
					<StyledAlert status={ AlertStatus.SUCCESS }>
						{ successMessage }
					</StyledAlert>
				) }
				<Container>
					<InputContainer>
						<Input
							type={ InputType.PASSWORD }
							label="API Key"
							name="api_key"
							placeholder="Enter Your API Key"
							value={ settings.api_key }
							onChange={ ( value ) =>
								handleInputChange( 'api_key', value )
							}
							disabled={ areSettingsSaved }
							suffix={
								areSettingsSaved ? (
									<Icon
										iconName={
											IconName.CHECK_MARK_CIRCLE_FILLED
										}
									/>
								) : (
									<Icon
										iconName={ IconName.WARNING_OUTLINED }
									/>
								)
							}
						/>
						<Input
							type={ InputType.PASSWORD }
							label="Client ID"
							name="client_id"
							placeholder="Enter Your Client ID"
							value={ settings.client_id }
							onChange={ ( value ) =>
								handleInputChange( 'client_id', value )
							}
							disabled={ areSettingsSaved }
							suffix={
								areSettingsSaved ? (
									<Icon
										iconName={
											IconName.CHECK_MARK_CIRCLE_FILLED
										}
									/>
								) : (
									<Icon
										iconName={ IconName.WARNING_OUTLINED }
									/>
								)
							}
						/>
					</InputContainer>
					<ButtonContainer>
						{ areSettingsSaved ? (
							<Button
								color={ ButtonColor.RED }
								onClick={ deleteSettings }
								disabled={ isDisconnecting }
								size={ ButtonSize.SMALL }
								icon={
									<Icon
										iconName={ IconName.DELETE }
										size={ IconSize.TINY }
									/>
								}
							>
								{ isDisconnecting
									? 'Disconnecting...'
									: 'Disconnect' }
							</Button>
						) : (
							<Button
								onClick={ saveSettings }
								disabled={ isSaving }
								size={ ButtonSize.SMALL }
								icon={
									<Icon
										iconName={ IconName.DONE }
										size={ IconSize.TINY }
									/>
								}
							>
								{ isSaving ? 'Saving...' : 'Save' }
							</Button>
						) }
					</ButtonContainer>
				</Container>
			</Section>
		</>
	);
};

const Container = styled.div`
	display: flex;
	flex-direction: column;
	height: 100%;
`;

const ButtonContainer = styled.div`
	display: flex;
	justify-content: flex-start;
	margin-top: 2rem;
	gap: 1rem;
`;

// Styled Alert component
const StyledAlert = styled( Alert )`
	margin-bottom: 20px;
`;

const InputContainer = styled.div`
	display: flex;
	flex-direction: row;
	flex-wrap: wrap;
	gap: 2rem;
	> * {
		flex: 1;
	}
`;

export default Setting;
