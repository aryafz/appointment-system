import { Edit, SimpleForm, TextInput, SelectInput } from 'react-admin';

const roles = [
    { id: 'super_admin', name: 'super_admin' },
    { id: 'site_owner', name: 'site_owner' },
    { id: 'operator', name: 'operator' },
    { id: 'user', name: 'user' },
];

const UserEdit = () => (
    <Edit>
        <SimpleForm>
            <TextInput source="email" />
            <SelectInput source="role" choices={roles} />
            <TextInput source="firstName" />
            <TextInput source="lastName" />
        </SimpleForm>
    </Edit>
);

export default UserEdit;
