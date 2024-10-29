import { HashRouter, Route, Routes } from 'react-router-dom';
import { StyledWordpressComponent } from '@refactco/ui-kit';
import Layout from './components/layout';
import Header from './components/header';
// use react-router-dom to create routes
import About from './pages/About';
import AddRule from './pages/Sync/AddRule';
import ImportCampaigns from './pages/ImportCampaigns/ImportCampaigns';
import { ToastContainer } from 'react-toastify';
import SyncRules from './pages/SyncRules/SyncRules';

/**
 * WordPress dependencies
 */
const { render } = wp.element;

const Settings = () => {
	return (
		<>
		<StyledWordpressComponent />
		<HashRouter>
			<Layout>
				<Header />
				<ToastContainer position="bottom-right" />
				<Routes>
					<Route path="/" element={ <SyncRules /> } />
					<Route path="/sync-rules/*" element={ <SyncRules /> } />
					<Route path="/sync-rules/add-new" element={ <AddRule /> } />
					<Route path="/about" element={ <About /> } />
					<Route
						path="/import-campaigns/*"
						element={ <ImportCampaigns /> }
					/>
				</Routes>
			</Layout>
		</HashRouter>
		</>
	);
};

render( <Settings />, document.getElementById( 'itcm-campaign-monitor' ) );
