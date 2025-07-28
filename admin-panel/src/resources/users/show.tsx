import { SimpleShowLayout, Show, TextField, EmailField } from 'react-admin';

const UserShow = () => (
    <Show>
        <SimpleShowLayout>
            <TextField source="id" />
            <EmailField source="email" />
            <TextField source="role" />
            <TextField source="firstName" />
            <TextField source="lastName" />
            <TextField source="createdAt" />
            <TextField source="updatedAt" />
        </SimpleShowLayout>
    </Show>
);

export default UserShow;
