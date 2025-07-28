import { Create, SimpleForm, TextInput, SelectInput } from 'react-admin';

const roles = [
    { id: 'super_admin', name: 'super_admin' },
    { id: 'site_owner', name: 'site_owner' },
    { id: 'operator', name: 'operator' },
    { id: 'user', name: 'user' },
];

const UserCreate = () => (
    <Create>
        <SimpleForm>
            <TextInput source="email" />
            <SelectInput source="role" choices={roles} />
            <TextInput source="firstName" />
            <TextInput source="lastName" />
            <TextInput source="password" type="password" />
        </SimpleForm>
    </Create>
);

export default UserCreate;
