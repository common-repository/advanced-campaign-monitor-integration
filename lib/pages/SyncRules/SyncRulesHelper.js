export const tabs = [
	{
		name: 'setting',
		title: 'Setting',
		route: '/sync-rules/setting',
	},
	{
		name: 'rules',
		title: 'Rules',
		route: '/sync-rules/sync',
	},
];

export function SyncRulesHelper( state, setState, navigate ) {
	function handleTabClick( tabIndex ) {
		setState( {
			...state,
			activeIndex: tabIndex,
		} );
		navigate( tabs[ tabIndex ].route );
	}

	return {
		handleTabClick,
	};
}
