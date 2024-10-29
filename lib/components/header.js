/* eslint-disable react-hooks/exhaustive-deps */
const { useState, useEffect } = wp.element;
import { Icon, Header as RefactHeader, IconName } from '@refactco/ui-kit';
import { useNavigate, useLocation } from 'react-router-dom';
import { Tooltip } from 'react-tooltip';

const Header = () => {
	const navigate = useNavigate();
	const location = useLocation();

	const [ activeItemIndex, setActiveItemIndex ] = useState( 0 );
	const items = [
		{
			item: 'sync-rules',
			title: 'Sync Rules',
			subHeaderItems: [
				{
					name: 'learn-how-add-sync-rule',
					title: 'Learn How to Add a New Sync Rule',
					icon: 
					<Icon
						iconName={
							IconName.INFO
						}
						className='learn-sync-info'
					/>
				}
				
			],
			onClick: () => {
				navigate( '/' );
			},
		},
		{
			item: 'import-campaigns',
			title: 'Import Campaigns',
			subHeaderItems: [
				{
					name: 'import_campaigns_title',
					title: 'Import your campaigns from Campaign Monitor',
				},
			],
			onClick: () => {
				navigate( '/import-campaigns' );
			},
		},
		{
			item: 'about',
			title: 'About',
			onClick: () => {
				navigate( '/about' );
			},
		},
	];

	const handleSelectItem = ( index ) => {
		setActiveItemIndex( index );
	};

	useEffect( () => {
		const path = location.pathname;

		const index = items.findIndex( ( item ) => {
			if ( item.item === 'settings' && path === '/' ) return true;
			return path.includes( item.item );
		} );
		setActiveItemIndex( index === -1 ? 0 : index );
	}, [ location.pathname ] );

	return (
		<>
		<RefactHeader
		logoSource={ 'Refact' }
		items={ items }
		onSelectItem={ handleSelectItem }
		activeItemIndex={ activeItemIndex }
	/>
		
		<Tooltip anchorSelect=".learn-sync-info" place="bottom-start" style={{background: '#003233'}}>
		<p>The Add New Rule page allows you to create a new rule for syncing user data with Campaign Monitor</p>
		<p>Here, you can define the specifics of what triggers the sync and what data gets synced.</p>
		<h2 style={{ color: 'white'}}>Steps to Add a New Rule:</h2>
		<ol>
			<li><b>Trigger Type:</b> Select the type of event that will trigger the sync. For example, “WP User Roles”.</li>
			<li><b>Trigger Object:</b> Choose the specific object related to the trigger, such as “Author”.</li>
			<li><b>List:</b> Specify the Campaign Monitor list where the data should be synced.</li>
			<li><b>Meta Mapping:</b> Map WordPress meta fields to Campaign Monitor custom fields. Click “Add a new row” to map additional fields.</li>
		</ol>
		</Tooltip>
		</>
		
	);
};

export default Header;
