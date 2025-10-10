"use client";

import { useState } from "react";
import { Label } from "@/components/ui/label";
import { Checkbox } from "@/components/ui/checkbox";
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
} from "@/components/ui/dropdown-menu";
import { Button } from "@/components/ui/button";
import { ChevronDown } from "lucide-react";

export const StatusFilter = () => {
  const statuses = [
    { id: "active", label: "Active" },
    { id: "leave", label: "On Leave" },
    { id: "resigned", label: "Resigned" },
    { id: "terminated", label: "Terminated" },
    { id: "probation", label: "On Probation" },
  ];

  const [selected, setSelected] = useState([]);

  const toggleStatus = (id) => {
    setSelected((prev) =>
      prev.includes(id) ? prev.filter((s) => s !== id) : [...prev, id]
    );
  };

  return (
    <div>
      <Label className="mb-2 block text-muted-foreground text-xs">
        Filter by Status
      </Label>

      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="outline"
            className="w-[180px] justify-between cursor-pointer text-muted-foreground"
          >
            {selected.length > 0 ? `${selected.length} selected` : "Status"}{" "}
            <ChevronDown />
          </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent className="w-[180px] p-2">
          {statuses.map((status) => (
            <div
              key={status.id}
              className="flex items-center space-x-2 cursor-pointer px-2 py-1 rounded-md hover:bg-muted"
              onClick={() => toggleStatus(status.id)}
            >
              <Checkbox
                className="cursor-pointer"
                checked={selected.includes(status.id)}
              />
              <span className="text-sm cursor-pointer">{status.label}</span>
            </div>
          ))}
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
};
