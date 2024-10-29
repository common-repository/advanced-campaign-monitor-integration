/* eslint-disable react-hooks/exhaustive-deps */
const { useState, useEffect } = wp.element;
import { Route, Routes, useNavigate, useLocation } from 'react-router-dom';

import Setting from '../Setting';
import Sync from '../Sync';
import {
	TabPanelContainer,
	TabItem,
	TabContent,
} from '../ImportCampaigns/ImportCampaigns';
import { SyncRulesHelper, tabs } from './SyncRulesHelper';

const SyncRules = () => {
	const [ state, setState ] = useState( {
		activeIndex: 0,
	} );

	const navigate = useNavigate();
	const location = useLocation();
	const helper = SyncRulesHelper( state, setState, navigate );
	const { handleTabClick } = helper;
	const { activeIndex } = state;

	useEffect( () => {
		if ( location.pathname.includes( 'setting' ) ) {
			setState( { ...state, activeIndex: 0 } );
		} else if ( location.pathname.includes( 'sync' ) ) {
			setState( { ...state, activeIndex: 1 } );
		} else {
			setState( { ...state, activeIndex: 0 } );
		}
	}, [ location.pathname ] );

	return (
		<>
			<TabPanelContainer>
				{ tabs.map( ( tab, index ) => (
					<TabItem
						key={ tab.name }
						isActive={ index === activeIndex }
						onClick={ () => handleTabClick( index ) }
					>
						{ tab.title }
					</TabItem>
				) ) }
			</TabPanelContainer>
			<TabContent>
				<Routes>
					<Route index element={ <Setting /> } />
					<Route path="setting" element={ <Setting /> } />
					<Route path="sync" element={ <Sync /> } />
				</Routes>
			</TabContent>
		</>
	);
};

export default SyncRules;
