import {useEffect, useState} from "react";
import {Badge, Button, Group, Modal, Stack, Table, Text, TextInput} from "@mantine/core";
import {SettingsPage, SettingsSection} from "../../../common/SettingsPage";
import {AdminPayoutRequest, payoutsClient} from "../../../../api/payouts.client.ts";
import {showError, showSuccess} from "../../../../utilites/notifications.tsx";

const AdminPayouts = () => {
    const [payouts, setPayouts] = useState<AdminPayoutRequest[]>([]);
    const [loading, setLoading] = useState(true);
    const [active, setActive] = useState<AdminPayoutRequest | null>(null);
    const [reference, setReference] = useState('');
    const [note, setNote] = useState('');
    const [saving, setSaving] = useState(false);

    const load = () => {
        setLoading(true);
        payoutsClient.getAdminPayouts()
            .then(setPayouts)
            .catch(() => {})
            .finally(() => setLoading(false));
    };

    useEffect(() => {
        load();
    }, []);

    const handleAction = async (status: 'PAID' | 'REJECTED') => {
        if (!active) return;
        setSaving(true);
        try {
            await payoutsClient.updateAdminPayout(active.id, {
                status,
                transfer_reference: reference || undefined,
                admin_note: note || undefined,
            });
            showSuccess(`Payout #${active.id} marked as ${status}.`);
            setActive(null);
            setReference('');
            setNote('');
            load();
        } catch (err: any) {
            showError(err?.response?.data?.message || 'Failed to update payout.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SettingsPage
            title="Organizer Payouts"
            description="Review and settle organizer wallet withdrawal requests from Paystack & Bachs.io ticket sales."
        >
            <SettingsSection title="Payout Requests">
                <Stack p="md">
                    {loading ? (
                        <Text size="sm" c="dimmed">Loading payout requests...</Text>
                    ) : payouts.length === 0 ? (
                        <Text size="sm" c="dimmed">No payout requests yet.</Text>
                    ) : (
                        <Table striped highlightOnHover>
                            <Table.Thead>
                                <Table.Tr>
                                    <Table.Th>Organizer</Table.Th>
                                    <Table.Th>Amount</Table.Th>
                                    <Table.Th>Bank Details</Table.Th>
                                    <Table.Th>Status</Table.Th>
                                    <Table.Th>Reference</Table.Th>
                                    <Table.Th>Action</Table.Th>
                                </Table.Tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {payouts.map((p) => (
                                    <Table.Tr key={p.id}>
                                        <Table.Td>
                                            <Text size="sm" fw={500}>{p.organizer_name}</Text>
                                            <Text size="xs" c="dimmed">{p.organizer_email}</Text>
                                        </Table.Td>
                                        <Table.Td fw={600}>{p.currency} {p.amount.toLocaleString()}</Table.Td>
                                        <Table.Td>
                                            <Text size="xs">{p.bank_name}</Text>
                                            <Text size="xs" c="dimmed">{p.account_number} · {p.account_name}</Text>
                                        </Table.Td>
                                        <Table.Td>
                                            <Badge color={p.status === 'PAID' ? 'green' : p.status === 'REJECTED' ? 'red' : 'orange'}>
                                                {p.status}
                                            </Badge>
                                        </Table.Td>
                                        <Table.Td><Text size="xs">{p.transfer_reference || '—'}</Text></Table.Td>
                                        <Table.Td>
                                            {p.status === 'PENDING' && (
                                                <Button size="xs" onClick={() => setActive(p)}>
                                                    Settle
                                                </Button>
                                            )}
                                        </Table.Td>
                                    </Table.Tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    )}
                </Stack>
            </SettingsSection>

            <Modal opened={!!active} onClose={() => setActive(null)} title={`Settle Payout #${active?.id}`}>
                {active && (
                    <Stack gap="md">
                        <Text size="sm">
                            <strong>{active.organizer_name}</strong> — {active.currency} {active.amount.toLocaleString()}
                        </Text>
                        <Text size="xs" c="dimmed">
                            {active.bank_name} · {active.account_number} ({active.account_name})
                        </Text>
                        <TextInput
                            label="Transfer Reference"
                            placeholder="e.g. PSK_TRF_123456 or Bank Ref"
                            value={reference}
                            onChange={(e) => setReference(e.currentTarget.value)}
                        />
                        <TextInput
                            label="Admin Note (Optional)"
                            placeholder="Settled via Paystack / Bank transfer"
                            value={note}
                            onChange={(e) => setNote(e.currentTarget.value)}
                        />
                        <Group justify="flex-end">
                            <Button color="red" variant="light" loading={saving} onClick={() => handleAction('REJECTED')}>
                                Reject
                            </Button>
                            <Button color="green" loading={saving} onClick={() => handleAction('PAID')}>
                                Mark as Paid
                            </Button>
                        </Group>
                    </Stack>
                )}
            </Modal>
        </SettingsPage>
    );
};

export default AdminPayouts;
