import { FieldMap, FieldMapCell, Input, Select } from '@refactco/ui-kit';
import { logger } from '../../../common/common-function';
const { Fragment, useEffect, useState } = wp.element;

const Fields = ( props ) => {
	const defaultTask = {
		wp: '',
		esp: '',
	};

	const { tasks, setTasks } = props;

	const [ wpFields, setWpFields ] = useState( [] );

	useEffect( () => {
		// api to get triggers
		wp.apiFetch( {
			path: '/re-esp/v1/campaign-monitor/wp-fields',
		} )
			.then( ( response ) => {
				setWpFields( response );
			} )
			.catch( ( error ) => {
				logger( error );
			} );
	}, [] );
	return (
		<FieldMap
			onAddItemClick={ () => {
				setTasks( [ ...tasks, defaultTask ] );
			} }
			onRemoveItemClick={ ( rowIndex ) => {
				tasks.splice( rowIndex, 1 );

				setTasks( [ ...tasks ] );
			} }
			headers={
				tasks.length > 0
					? [
							{
								title: 'WordPress',
							},
							{
								title: 'Campaign Monitor',
							},
					  ]
					: []
			}
		>
			{ tasks.map( ( item, itemIndex ) => {
				return (
					<Fragment key={ itemIndex }>
						<FieldMapCell>
							<Select
								value={ tasks[ itemIndex ].wp }
								onChange={ ( value ) => {
									tasks[ itemIndex ].wp = value;

									setTasks( [ ...tasks ] );
								} }
							>
								<option
									value="0"
									selected={ true }
									disabled={ tasks[ itemIndex ].wp !== '' }
								>
									Select a Data Type
								</option>
								{ wpFields.map( ( field, index ) => (
									<option key={ index } value={ field.value }>
										{ field.label }
									</option>
								) ) }
							</Select>
						</FieldMapCell>
						<FieldMapCell>
							<Input
								placeholder="Campaign Monitor Field"
								value={ tasks[ itemIndex ].esp }
								onChange={ ( value ) => {
									tasks[ itemIndex ].esp = value ?? '';
									setTasks( [ ...tasks ] );
								} }
							/>
						</FieldMapCell>
					</Fragment>
				);
			} ) }
		</FieldMap>
	);
};

export default Fields;
